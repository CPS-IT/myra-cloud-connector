==============
TYPO3 Features
==============

Toggle TYPO3 features.

..  figure:: /img/TYPO3Features.png
    :width: 931 px
    :alt: view of TYPO3 feature settings

.. _production-only:
Production only
~~~~~~~~~~~~~~~

If checked, this prevents any Myra Cloud cache clear request and disables all UI elements if the
application context is not :php:`Production*`. This is useful to prevent clear requests from stage
or development instances.

.. _admin-only:
Admin Only UI Elements
~~~~~~~~~~~~~~~~~~~~~~

If checked, all UI Elements are disabled for non-admin users.

..  note::
    Auto-clear hooks (page update / file list replace) are not affected by this.

.. _disable-hooks:
Disable Hooks
~~~~~~~~~~~~~

If checked, both auto-clear hooks (page update and file list replace) are disabled.

.. _domain-blacklist:
Domain Blacklist
~~~~~~~~~~~~~~~~

A comma separated blacklist of domain names to prevent any Myra Cloud clear cache request and
disable all UI elements if TYPO3 is used from a domain in this list.

..  note::
    This does not affect the CLI command.

..  versionchanged:: 3.0.0
    Since version 3.0.0, the :php:`domainBlacklist` configuration may contain values which are
    parseable by PHP's :php:`fnmatch` function. Example: :php:`*.ddev.site`
