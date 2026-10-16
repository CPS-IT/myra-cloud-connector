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

Myra API setup

..  _myra-api-endpoint:

Myra API Endpoint
~~~~~~~~~~~~~~~~~

The Myra Cloud API endpoint for every request.

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

Bearer token for the Myra Cloud API v2. Only used when the endpoint is
:php:`apiv2.myracloud.com`.

..  _myra-required-credentials:

Required credentials
~~~~~~~~~~~~~~~~~~~~

The extension stays inactive until the credentials of the API version in use
are set. The settings of the other version may stay empty:

-   API v2 (endpoint :php:`apiv2.myracloud.com`): endpoint and token
-   API v1 (any other endpoint): endpoint, API key and API secret

..  versionchanged:: 3.3.3 / 4.1.4
    Before, every Myra setting had to be set, including the token, so a v1
    setup without a token left the extension inactive.

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
