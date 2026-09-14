# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

TYPO3 CMS extension `myra_cloud_connector` (Composer package `cpsit/myra-cloud-connector`, extension key `myra_cloud_connector`). It connects a TYPO3 installation to a [Myra Cloud](https://www.myrasecurity.com/en/) instance (a CDN/WAF), letting editors and automated hooks flush Myra's edge cache for pages and file resources whenever the corresponding TYPO3 content changes.

Targets TYPO3 `14.3.6-14.3.99` and PHP `~8.2.0 || ~8.3.0 || ~8.4.0 || ~8.5.0` (see `ext_emconf.php` / `composer.json`).

## Commands

All commands run through Composer scripts (`composer.json`):

- `composer lint` — runs `lint:composer` (composer-normalize --dry-run), `lint:editorconfig` (`ec --git-only`), `lint:php` (php-cs-fixer dry-run)
- `composer fix` — auto-fixes the same three (composer normalize, editorconfig, php-cs-fixer)
- `composer sca:php` — PHPStan, level 8, analyses `Classes`, `Configuration`, `Tests` (config: `phpstan.neon`)
- `composer migration:rector` — runs Rector (`rector.php`); CI runs this with `--dry-run`
- `composer analyze:dependencies` — `composer-dependency-analyser` (config: `composer-dependency-analyser.php`)
- `composer docs` — builds the Sphinx/t3docs documentation via Docker Compose (`docs:cleanup`, `docs:build`, `docs:open`)

There is currently **no working test suite**: `Tests/Unit` only contains a `.gitkeep`, there is no `test` Composer script, and `phpunit.xml` points at a bootstrap (`.Build/vendor/typo3/testing-framework/...`) that isn't installed (`typo3/testing-framework` isn't even in `composer.json`). Don't assume `vendor/bin/phpunit` works without first wiring up the testing framework dependency and a real bootstrap.

CI (`.github/workflows/cgl.yaml`) runs, in order: composer validate, `analyze:dependencies`, `lint:composer`, `lint:editorconfig`, `lint:php`, `migration:rector --dry-run`, `sca:php`, a docs build, and a check that TER vendor bundling (see below) still works via `composer extract-dependencies --print-file-contents --fail`.

Local dev environment is DDEV (`.ddev/config.yaml`: TYPO3 project, docroot `public`, PHP 8.3) — use `ddev start` / `ddev composer ...` / `ddev exec ...` as usual.

## Architecture

### Adapter pattern for cache-clearing backends

`Classes/Adapter/AdapterInterface` is the abstraction for "a system that can clear an external cache for a given TYPO3 site/page/file". `BaseAdapter` (abstract, implements `SingletonInterface`) holds all backend-agnostic permission logic, read once per request and cached statically:

- `canExecute()` = extension is fully configured (all `getAdapterConfigPrefix()`-prefixed settings non-empty) AND (`onlyLive` is off OR context is Production) AND current request domain is not in `domainBlacklist` (comma list, `fnmatch` patterns allowed).
- `canInteract()` = `canExecute()` AND (`onlyAdmin` is off OR current BE user is admin). Gates interactive UI (buttons, context menu, menu items).
- `canAutomated()` = `canExecute()` AND `disableHooks` is off. Gates the DataHandler hook and file-replace listener.

These three gates are checked centrally at the adapter, not duplicated by callers. Extension configuration values support an `ENV=VAR_NAME` syntax to source secrets from environment variables instead of the TYPO3 backend config (see `ext_conf_template.txt`).

`MyraApiAdapter extends BaseAdapter` is the only concrete adapter and is tagged `#[AutoconfigureTag('myra_cloud.external.cache.adapter')]`. It talks to the Myra API via the bundled `cpsit/myra-web-api` client, choosing `WebApiV2` (bearer token) or the legacy `WebApi` (key/secret) depending on configuration. It resolves a TYPO3 site's configured Myra domain names to Myra's internal domain IDs and FQDN/DNS records, with results cached in `@cache.runtime`.

`AdapterProvider` collects all tagged adapters via `#[AutowireIterator('myra_cloud.external.cache.adapter')]`. `getDefaultProviderItem()` currently just returns the first registered adapter — the tag-based collection exists to support multiple adapters, but today there is exactly one and nothing picks between them. Adapters are wrapped in the `ProviderItem` DTO for consumption outside the `Adapter` namespace.

### Service layer / call graph

`Service/ExternalCacheService::clear(Typo3CacheType $type, ?string $identifier, ?int $languageId)` is the single entry point that actually talks to an adapter. It dispatches on the `Typo3CacheType` enum (`PAGE`, `RESOURCE`, `ALL_PAGE`, `ALL_RESOURCES`) to private handlers that resolve TYPO3 state into DTOs, then loop over `SiteService::getSitesForClearance()` calling `AdapterInterface::clearCache()` per site. `ALL_RESOURCES` additionally clears every FAL storage's root folder plus three fixed pseudo-locations (`ExtensionAsset`, `Typo3Core`, `Typo3Temp` — i.e. `typo3conf`/extension assets, TYPO3 core assets, `typo3temp`).

`PageService`/`SiteService` wrap `PageRepository`/`SiteFinder`. A TYPO3 site is only "supported" (eligible for clearing) if its site configuration has non-empty `myra_host` (see below) — `SiteService::isSiteSupported()`. Lookups are cached in `@cache.runtime` since a single request can trigger many clear calls.

Nothing calls `ExternalCacheService::clear()` directly anymore. All triggers dispatch a PSR-14 `Event\ClearMyraCloudCacheEvent(Typo3CacheType $type, ?string $identifier, ?int $languageId)` instead (see `Documentation/Reference/Events.rst`); `EventListener\ExternalClearCacheListener` is the default (and currently only) listener — it resolves page/language via `PageService` when needed, de-dupes via `@cache.runtime` (key `MyraCloudConnector_ExternalClearCache_<identifier>_<languageId>`), calls `ExternalCacheService::clear()`, and reports success back via `$event->setCacheResult()`. It performs no permission check itself — see "Known issue" below for where gating actually happens.

Dispatchers of `ClearMyraCloudCacheEvent`:
- `Command/MyraCloudClearCommand` — CLI `myracloud:clear -t <page|resource|all|allresources> [-i identifier] [-l language]`.
- `Controller/ExternalClearCacheController` — the `ajax_external_cache_clear` backend AJAX route (see `Configuration/Backend/AjaxRoutes.php`), called from the frontend JS module.
- `DataHandler/DataHandlerHook::clearCachePostProc` — registered in `ext_localconf.php` on the legacy `TYPO3_CONF_VARS[...]['clearCachePostProc']` hook. The hook payload is a union type: `{table, uid, uid_page}` for a single-record change (resolves the record's page + language via `TcaSchemaFactory`, dispatches for just that page, non-recursive) or `{cacheCmd, tags}` for a cache-tag-based clear, which is forwarded to `cache.myracloud` (see "Cache-tag flush" below) instead of being dispatched directly. Has its own early `canAutomated()` check before doing either.
- `EventListener/ExternalClearResourceCacheListener` — PSR-14 `AfterFileCommandProcessedEvent`, fires only on file `upload` with `DuplicationBehavior::REPLACE` (i.e. "Replace" in the file list), dispatches for the file and its processed-file variants. Also has its own early `canAutomated()` check.
- `Cache/MyraCacheBackend` — see "Cache-tag flush" below; dispatches independently of the hook above whenever TYPO3's `pages` cache group is flushed by any code path, with no `canAutomated()`/`canExecute()` check of its own.

**Known issue:** permission gating for `ClearMyraCloudCacheEvent` is inconsistent across dispatchers. `ExternalClearCacheListener` itself performs no check at all; `canAutomated()` (the "Disable Hooks" setting) is only checked by `DataHandlerHook` and `ExternalClearResourceCacheListener`, which early-return *before* dispatching — `Command/MyraCloudClearCommand`, `Controller/ExternalClearCacheController`, and `Cache/MyraCacheBackend` dispatch unconditionally. The only check left downstream is `canExecute()` inside `MyraApiAdapter::clearCache()` (extension configured + `onlyLive`/Production + `domainBlacklist`, but *not* `disableHooks`). Net effect: setting `disableHooks = 1` does **not** actually disable the cache-tag-flush AutoClear mechanism, contradicting `ext_conf_template.txt` and `Documentation/Configuration/Typo3Features.rst`, which document "Disable Hooks" as disabling all three AutoClear mechanisms (page update, file replace, cache tag flush). Verify whether this is intentional before relying on it.

### Cache-tag flush (`Classes/Cache/`)

A second, TYPO3-core-driven path to `ClearMyraCloudCacheEvent` that doesn't go through the legacy `clearCachePostProc` hook at all: this extension registers its own cache, `myracloud` (`ext_localconf.php` → `$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['myracloud']`), as a member of TYPO3's `pages` cache group, backed by `Cache\MyraCacheBackend` (extends `Typo3DatabaseBackend`) / `Cache\MyraCacheFrontend` (extends `AbstractFrontend`). Because it's a `pages`-group member, TYPO3's own `CacheManager` calls its `flush()`/`flushByTag()`/`flushByTags()` whenever *anything* flushes that group (DataHandler, "Flush all caches", console `cache:flush`, other extensions) — independent of whether the legacy DataHandler hook fires.

- `EventListener/PagesCacheListener` (`AfterCachedPageIsPersistedEvent`) writes one `myracloud` entry per rendered/cached page, under the same cache identifier and tags as the actual page-cache entry, with the resolved page ID as its value (via `MathUtility`/`pageId_*` tag convention). This is what makes tag → page-ID lookups possible later.
- `MyraCacheBackend::flushByTag()`/`flushByTags()` look up the page ID stored under a given tag (`getByTag()`, via the caching framework's tags table) and dispatch `ClearMyraCloudCacheEvent(Typo3CacheType::PAGE, $pageId)` for it, before calling through to `parent::flushByTag()` to actually remove the DB rows.
- `MyraCacheBackend::flush()` dispatches `ClearMyraCloudCacheEvent(Typo3CacheType::ALL_PAGE)` (a full "pages" group flush clears every page in Myra Cloud too), then calls `parent::flush()`.
- A page can only be resolved this way once it has actually been cached at least once since the last flush (see `Documentation/Reference/AutoClear-Hooks.rst` → "Cache Tag Flush"); `Classes/Exception/*` are thrown for invalid cache entries/identifiers/tags or a misconfigured frontend (`MyraCacheBackend::setCache()` only accepts a `MyraCacheFrontend`).
- Both `MyraCacheBackend`/`MyraCacheFrontend` are marked `@internal` — not an extension point, unlike `ClearMyraCloudCacheEvent` itself.
- Requires `typo3/cms-frontend` (promoted from `require-dev` to `require` in this feature) for `AfterCachedPageIsPersistedEvent`.

Backend UI listeners only render buttons/menu entries (gated by `canInteract()`); the actual clear request is fired client-side by `Resources/Public/JavaScript/clear-cache-actions.js` against the AJAX route above:
- `EventListener/ExternalClearCacheButtonListener` — `ModifyButtonBarEvent`, adds a toolbar button in page module / file list, restricted to specific backend routes (`getBackendRoute()` match).
- `EventListener/ExternalClearCacheFileListListener` — `ProcessFileListActionsEvent`, adds a per-row action in the file list (currently a no-op pending a TYPO3 core change, see the `@todo` in that class).
- `EventListener/ExternalClearCacheMenuItemListener` — `ModifyClearCacheActionsEvent`, adds "clear all pages"/"clear all resources" entries to the global clear-cache dropdown.
- `ContextMenu/ExternalClearCacheContextMenuItemProvider` — page tree / file list right-click menu entry (pages, or folders only — files already have a file-list action).

### Domain DTOs (`Classes/Domain/DTO/Typo3/...`)

Plain value objects (excluded from DI autowiring in `Configuration/Services.yaml`) that wrap native TYPO3 concepts (`Page`, FAL files/folders, `Site`) behind narrow interfaces (`PageSlugInterface`, `PageIdInterface`, `SiteConfigInterface`, `FileInterface`, etc.), so the `Adapter` layer never depends on TYPO3 core classes directly — only on these interfaces. `File/` has variants for real FAL objects (`File`), for paths that can't be resolved to a FAL object (`CustomFile`), and for the three fixed pseudo-folders always cleared with `ALL_RESOURCES` (`ExtensionAsset`, `Typo3Core`, `Typo3Temp`).

### Site configuration integration

`Configuration/SiteConfiguration/Overrides/sites.php` adds a `myra_host` field to TYPO3's site configuration ("Myra Cloud" tab). `Typo3SiteConfig` reads that field (comma-separated list, via `DomainListParserTrait`) and exposes it as `getExternalIdentifierList()` — this is what makes a site "supported" and is the list of Myra domain identifiers a clear request is sent for.

### TER packaging

Because TYPO3 Extension Repository (non-Composer) installs can't rely on a Composer `vendor/` dir, this extension's runtime Composer dependencies (`cpsit/myra-web-api`, `s1lentium/iptools`, guzzle, etc.) are bundled into `Resources/Private/Libs/vendor` via `eliashaeussler/typo3-vendor-bundler` (`composer extract-dependencies`). `packaging_exclude.php` lists dev-only files/directories stripped from the TER release archive. Don't hand-edit `Resources/Private/Libs/vendor` — it's generated.
