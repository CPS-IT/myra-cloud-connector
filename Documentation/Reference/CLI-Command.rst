===========
CLI command
===========

The command is based on a Symfony command and is only accessible via CLI (not via scheduler)

.. _cli-command:
Command
-------

.. code:: shell

   vendor/bin/typo3 myracloud:clear

.. _cli-usage:
Usage
-----

.. code:: shell

     myracloud:clear [options]
     myracloud:clear -t page -i [PAGE_UID like: 123]
     myracloud:clear -t resource -i [PATH like: /fileadmin/path/To/Directory]
     myracloud:clear -t resource -i [PATH like: /assets/myCustomAssets/myScript.js]
     myracloud:clear -t resource -i [PATH like: /fileadmin/path/ToFile.jpg]
     myracloud:clear -t all
     myracloud:clear -t allresources

.. _cli-type:
Type Parameter
-----

``-t, --type=TYPE``

-  page
-  resource
-  all
-  all resources

.. _cli-type-page:
--type=page
~~~~~~~~~~~

``page`` type requires a page identifier (pid) ``-i 1``. the pid must be
numeric.

``page`` clear commands are never recursive

.. _cli-type-resource:
--type=resource
~~~~~~~~~~~~~~~

``resource`` type requires a uri identifier ``-i /path/to/something``.

``resource`` clear commands are always recursive

.. note::
    The ``resource`` type can also be used to clear pages. Simply provide the site URI (this will be recursive!).

.. _cli-type-allresources:
--type=allresources
~~~~~~~~~~~~~~~~~~~

``allresources`` type requires NO extra option.

This will clear everything from these folders :

-  /fileadmin/\*
-  /typo3/\*
-  /typo3temp/\*
-  /typo3conf/\*

The ``allresources`` clear command is recursive by default.


.. _cli-type-all:
--type=all
~~~~~~~~~~

``all`` type requires no extra option.

This clears everything in Myra Cache for this TYPO3 Instance.

The ``all`` clear command is recursive by default.
