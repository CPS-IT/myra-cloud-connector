=================
AutoClear - Hooks
=================

Two AutoClear hooks are implemented:

-  Page update
-  File Overwrite

.. note::
    - Hooks are not affected by the :ref:`Admin Only<_admin-only>` setting
    - Hooks can be disabled via :ref:`Disable Hooks<_disable-hooks>` setting

.. _page-update-hook:
Page Update
-----------

This hook listens on the ``DataHandler->clearCachePostProc`` interface.

It will only clear the page itself, when the page or its elements are edited (created/updated/deleted).
It will not clear subpages and file resources.

.. _file-replace-hook:
File Replace
--------------

.. image:: ../img/context_filelist.png
    :alt: view of fileList context menu showing Myra Clear Cache option

Using the option "Replace" in FileList will trigger this hook. This also
clears processed files from the cache.
