..  _auto-clear-hooks:

=================
AutoClear - Hooks
=================

Three AutoClear mechanisms are implemented:

-  Page update
-  File Overwrite
-  Cache tag flush

.. note::
    - Hooks are not affected by the :ref:`Admin Only<_admin-only>` setting
    - Hooks can be disabled via :ref:`Disable Hooks<_disable-hooks>` setting, but this
      only affects this extension's own hooks / event listeners, not hooks registered
      by third-party extensions (e.g. EXT:news' data handler hook)
    - All of them clear the cache by dispatching a
      :ref:`ClearMyraCloudCacheEvent <clear-myra-cloud-cache-event>`, see
      :ref:`events`

..  _page-update-hook:

Page Update
-----------

This hook listens on the :php:`DataHandler->clearCachePostProc` interface.

It will only clear the page itself, when the page or its elements are edited (created/updated/deleted).
It will **not** clear subpages and file resources (non-recursive). Pages with
:typoscript:`TCEMAIN.clearCache_disable` and workspace versions are not cleared,
as TYPO3 does not clear them either.

Deleting a page clears the URL it had, in the language of the deleted record;
deleting a page tree clears every page of it. A page that was not public when
it was deleted (hidden, or outside its start and end time, also when only its
default language page was) is not cleared: it was not served.

Other pages showing the changed record are cleared by the
:ref:`Cache Tag Flush <cache-tag-flush-hook>` mechanism below. So are cache
clears TYPO3 requests by command, for example "Clear cache for this page" or
:typoscript:`TCEMAIN.clearCacheCmd`.

..  versionchanged:: 3.4.0 / 4.2.0
    Since 3.3.0 / 4.1.0, record changes only reached Myra Cloud through the
    cache tag flush, which clears a page only while TYPO3 holds a cache entry
    for it. A deleted page was never cleared; through the cache tag flush, the
    start page of every site was cleared instead.

..  _file-replace-hook:

File Replace
--------------

..  figure:: /img/context_filelist.png
    :width: 283 px
    :alt: View of File list context menu showing Myra clear cache option

Using the option "Replace" in FileList will trigger this hook. This also
clears processed files from the cache.

..  _cache-tag-flush-hook:

Cache Tag Flush
----------------

..  versionadded:: 3.3.0 / 4.1.0
    `Feature #156 - Introduce cache tag based auto-clear flush for MyraCloud <https://github.com/CPS-IT/myra-cloud-connector/pull/156>`__

TYPO3 tags every cached page with cache tags (its own page ID, the
tables/records rendered on it, etc.) and flushes pages by tag whenever a
change makes them outdated - independently of, and often covering more
pages than, the :ref:`Page Update<page-update-hook>` hook above (e.g. when a
record is shared across several pages).

This extension registers its own cache (:php:`myracloud`) as a member of
TYPO3's :php:`pages` cache group. Whenever that group is flushed:

-  by cache tag, the page that was cached under that tag is cleared in Myra
   Cloud,
-  entirely (e.g. "Flush all caches"), **all** pages are cleared in Myra
   Cloud.

..  note::
    A page can only be resolved from a cache tag once it has actually been
    rendered and cached at least once. Pages that were never cached, or
    whose cache entry has already expired or been flushed, cannot be
    resolved this way. How long an entry is kept: see
    :ref:`Lifetime of the page cache entries <myra-cache-lifetime>`.
