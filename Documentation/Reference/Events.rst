..  _events:

======
Events
======

..  _clear-myra-cloud-cache-event:

ClearMyraCloudCacheEvent
------------------------

..  versionadded:: 3.3.0 / 4.1.0
    `Feature #155 - Introduce and use ClearMyraCloudCacheEvent for cache flush <https://github.com/CPS-IT/myra-cloud-connector/pull/155>`__

:php:`CPSIT\MyraCloudConnector\Event\ClearMyraCloudCacheEvent` is a PSR-14
event dispatched by every built-in trigger of a Myra Cloud cache clear
request:

-  the :ref:`CLI command<cli-command>`
-  the backend AJAX endpoint used by the clear-cache buttons, the clear-cache
   menu entries and the context menu action
-  the AutoClear hooks (:ref:`page update<page-update-hook>` and
   :ref:`file replace<file-replace-hook>`)

..  code-block:: php

    final class ClearMyraCloudCacheEvent
    {
        public function __construct(
            public readonly Typo3CacheType $type,
            public readonly ?string $identifier = null,
            public readonly ?int $languageId = null,
        ) {}

        public function getCacheResult(): ?bool;
        public function setCacheResult(bool $result): void;
    }

:php:`type`, :php:`identifier` and :php:`languageId` describe what should be
cleared (see :ref:`CLI command<cli-usage>` for the meaning of these values
per type). A listener is expected to report the outcome of the clear request
via :php:`setCacheResult()`; callers read it back via :php:`getCacheResult()`.

..  _clear-myra-cloud-cache-event-default-listener:

Default listener
----------------

:php:`CPSIT\MyraCloudConnector\EventListener\ExternalClearCacheListener` is
registered by default. It resolves the affected page (for the `page` type
when no explicit language is given), forwards the request to the configured
Myra adapter, de-duplicates repeated requests for the same
identifier/language within a request via the TYPO3 runtime cache, and
reports the result back on the event.

..  _clear-myra-cloud-cache-event-usage:

Custom listeners
----------------

Register an additional listener on :php:`ClearMyraCloudCacheEvent` (via
:php:`#[AsEventListener]` or :file:`Configuration/Services.yaml`) to react to
a clear request, for example to clear an additional external cache alongside
Myra Cloud, or to log specific requests.
