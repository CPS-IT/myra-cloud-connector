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
    and are non-recursive.

.. _topbar-page:
Topbar clear cache menu
-----------------------

..  figure:: /img/cacheMenu.png
    :alt: View of main cache menu showing Myra Clear cache option

    Clear the entire page cache or file cache

This option is admin-only by default. To allow for non-admin users, set user permissions accordingly.

If the menu option does not appear, check for missing Myra credentials and the setting of the feature flags:

-  :ref:`Admin only<_admin-only>`
-  :ref:`Production only<_production-only>`
-  :ref:`Domain blacklist<_domain-blacklist>`

.. _contextmenu-page:
Context menu in page tree
-------------------------

..  figure:: /img/page_context.png
    :alt: View of page context menu showing Myra Clear Cache option

This will clear the selected page only (no child pages). The option will not clear file resources.

.. _buttonbar-page:
Button bar
----------

..  figure:: /img/page_buttonbar.png
    :alt: View of page buttonbar showing Myra Clear Cache option

This will clear the current page only (no child pages). The option will not clear file resources.

See also
=========

:ref:`AutoClear on page / content changes <_page-update-hook>`
