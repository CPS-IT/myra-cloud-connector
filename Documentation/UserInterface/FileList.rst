===========
Clear files
===========

There are 4 ways to clear files from the cache:

-  Topbar clear cache menu (clear all)
-  Context menu in file tree (left/right-click)
-  Options menu in file list
-  File list update hook

..  note::
    The file tree clear cache commands on folders are recursive.

.. _topbar-files:
Topbar clear cache menu
-----------------------

..  figure:: /img/cacheMenu.png
    :width: 353 px
    :alt: The TYPO3 cache menu showing additional Myra cache options to delete all files or all pages.

    Clear the entire page cache or file cache

This option is admin-only by default. To allow for non-admin users, set user permissions accordingly.

If the menu option does not appear, check for missing Myra credentials and the setting of the feature flags:

-  :ref:`Admin only<_admin-only>`
-  :ref:`Production only<_production-only>`
-  :ref:`Domain blacklist<_domain-blacklist>`

.. _contextmenu-filetree:
Context menu in file tree
-------------------------

..  figure:: /img/context_filetree.png
    :width: 283 px
    :alt: View of File tree context menu showing Myra clear cache option

This will clear all files from the selected directory and all its subdirectories recursively.

.. _optionsmenu-filelist:
Options menu in file list
-------------------------

..  figure:: /img/context_filelist.png
    :width: 283 px
    :alt: Screenshot of the File list context menu showing Myra clear cache option

This will clear the selected file only.

See also
=========

:ref:`AutoClear on file replace <_file-replace-hook>`
