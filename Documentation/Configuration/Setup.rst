..  _extension-setup:

===============
Extension Setup
===============

Most settings are made in
:guilabel:`Admin Tools > Settings > Extension Configuration > myra_cloud_connector`.

..  _env-variables:

Use of environment variables
----------------------------

Configuration values can be set as text or parsed from environment values:

..  figure:: /img/env_inject.png
    :width: 931 px
   :alt: Extension configuration Settings

Every configuration value that starts with :php:`ENV=` will be parsed and the provided
environment variable will be used.

Example syntax:

-   Configuration: :php:`ENV=MYRA_API_KEY`
-   Result: :php:`getenv('MYRA_API_KEY')`

Values may also come from :file:`config/system/settings.php` or a configuration
loader. Typed values are accepted there: integers and booleans (for example
:yaml:`onlyLive: 0`) are read as :php:`'0'` / :php:`'1'`.

..  _myra-settings:

Myra Settings
-------------

The extension talks to Myra Cloud through the API v2 only, using the library
`cpsit/myra-web-api <https://github.com/CPS-IT/myra-web-api>`__. Both kinds of
credentials work with it: a token, or an API key with its secret.

..  _myra-api-endpoint:

Myra API Endpoint
~~~~~~~~~~~~~~~~~

:php:`apiv2.myracloud.com` or :php:`api.myracloud.com` (the former API v1
default): Myra's API v2 at :php:`https://apiv2.myracloud.com`. Empty: the extension
stays inactive.

Any other value is used as base URI of the API v2 instead, for example a proxy or a
local emulator (:php:`http://127.0.0.1:8080`). Without a scheme, :php:`https://` is
added.

..  _myra-api-key:

Myra API Key
~~~~~~~~~~~~

A Myra Cloud User API key (the user needs permissions to clear the domain cache).

..  _myra-api-secret:

Myra API Secret
~~~~~~~~~~~~~~~

The matching secret for the API key.

..  _myra-api-token:

Myra API Token
~~~~~~~~~~~~~~

A Myra Cloud API token, as alternative to API key and secret.

..  _myra-required-credentials:

Required credentials
~~~~~~~~~~~~~~~~~~~~

The extension stays inactive until the endpoint and a token, or an API key and its
secret, are set.
If both are set, the endpoint decides, as it did before:

-   endpoint :php:`apiv2.myracloud.com`: the token
-   any other endpoint: API key and secret

If those are incomplete, the other credentials are used.

..  versionchanged:: 3.3.3 / 4.1.4
    Before, every Myra setting had to be set, including the token, so a v1
    setup without a token left the extension inactive.

..  versionchanged:: 3.4.0 / 4.2.0
    Only the API v2 is used, with either kind of credentials. Before, the endpoint
    chose the API version: the token only worked with :php:`apiv2.myracloud.com`,
    key and secret only with the API v1. Existing settings keep working unchanged.

..  _typo3-site-settings:

TYPO3 Site Settings
~~~~~~~~~~~~~~~~~~~

To link TYPO3 with Myra Cloud, it's necessary to announce the used Myra Cloud Domains for a TYPO3 Site Entity.
This can be done in :guilabel:`Site Management > Edit [SiteXYZ] > Myra Cloud > Myra Domain List`.

The configuration field accepts a comma separated list of all Myra domains. These are the domains which are
supported by this particular site. You can find the correct names in the
`Myra Cloud Backend <https://dashboard.myracloud.com>`__.

..  figure:: /img/myra_websites.png
    :alt: Myra Backend Website List

..  _purge-scope:

Which hosts are cleared
~~~~~~~~~~~~~~~~~~~~~~~

A Myra domain usually carries several hosts, for example ``www``, ``api`` and
``staging``. A cache clear reaches only those active DNS records of the Myra
domain that are hosts of a TYPO3 site: the host of the site base (with the base
variant of the current application context) and of its language bases.

-   A page clear reaches the hosts of the page's site.
-   A file clear and "clear all" reach the hosts of all sites of this TYPO3
    instance, never the whole Myra domain.

If none of the domain's records is a host of the site, nothing is cleared and a
log entry names the domain and the site's hosts. Add a missing host to the site
as a base variant or a language base.

..  versionchanged:: 3.3.3 / 4.1.4
    Before, every clear reached all active DNS records of the Myra domain,
    including the hosts of other sites and environments.

..  _myra-cache-lifetime:

Lifetime of the page cache entries
----------------------------------

For the :ref:`Cache Tag Flush <cache-tag-flush-hook>`, the extension keeps an
entry for every rendered page in its own cache (:php:`myracloud`). An entry
lives as long as the page's cache entry in TYPO3, and at least the default
lifetime of the :php:`myracloud` cache: 3600 seconds unless configured.

If Myra Cloud keeps pages longer than that, set the default lifetime to at
least the Myra Cloud cache time, for example in
:file:`config/system/additional.php`:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['myracloud']['options']['defaultLifetime'] = 86400;

Otherwise a cache tag flush no longer clears a page in Myra Cloud once its
entry has expired, while Myra Cloud still serves the page: no request reaches
TYPO3 that would store the entry again. :php:`0` keeps the entries without
expiry.

..  versionchanged:: 3.4.0 / 4.2.0
    Before, every entry lived 3600 seconds, also when TYPO3 cached the page
    longer, and the default lifetime could not be configured.
