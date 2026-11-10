..  _clear-pages:

===========
Clear pages
===========

There are 4 ways to clear a page from the cache:

-  Topbar clear cache menu (clear all)
-  Context menu in page tree (left/right-click)
-  Button bar in doc header within Page/List/View modules
-  Page/Content update hook

..  note::
    Apart from the topbar clear cache menu, the page clear cache options clear a single page only
    and are non-recursive, unless the page is configured to require a
    :ref:`recursive cache clear <recursive-page-clear>`.

..  _topbar-page:

Topbar clear cache menu
-----------------------

..  figure:: /img/cacheMenu.png
    :width: 353 px
    :alt: Screenshot of main cache menu showing Myra Clear cache option

    Clear the entire page cache or file cache

This option is admin-only by default. To allow for non-admin users, set user permissions accordingly.

If the menu option does not appear, check for missing Myra credentials and the setting of the feature flags:

-  :ref:`Admin only<_admin-only>`
-  :ref:`Production only<_production-only>`
-  :ref:`Domain blacklist<_domain-blacklist>`

..  _contextmenu-page:

Context menu in page tree
-------------------------

..  figure:: /img/page_context.png
    :width: 278 px
    :alt: Screenshot of the page context menu showing Myra Clear Cache option

This will clear the selected page only (no child pages, unless :ref:`configured otherwise
<recursive-page-clear>`). The option will not clear file resources.

..  _buttonbar-page:

Button bar
----------

..  figure:: /img/page_buttonbar.png
    :width: 234 px
    :alt: Screenshot of the page buttonbar showing Myra Clear Cache option

This will clear the current page only (no child pages, unless :ref:`configured otherwise
<recursive-page-clear>`). The option will not clear file resources.

..  _recursive-page-clear:

Recursive cache clear per page
------------------------------

..  versionadded:: 3.4.0
    `Feature #177 - Allow recursive page cache clear via page setting <https://github.com/CPS-IT/myra-cloud-connector/pull/177>`__

By default, clearing a page only clears that single page in Myra Cloud. For pages
whose output also affects other URLs (e.g. a new detail page), the page can be
flagged to be cleared recursively:

1.  Edit the page properties and switch to the tab **Myra Cloud**.
2.  Enable **Require recursive cache clear** and save.

From then on, every clear request for this page (context menu, button bar,
:ref:`AutoClear hooks <auto-clear-hooks>`, CLI command with ``-t page``) also clears
all paths below the page's URL in Myra Cloud. The setting is stored per page
record (field :php:`tx_myracloud_require_recursive_cache_clear`) and is not
inherited by child pages.

See also
=========

:ref:`AutoClear on page / content changes <_page-update-hook>`
