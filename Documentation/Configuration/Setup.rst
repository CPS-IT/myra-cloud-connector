===============
Extension Setup
===============

Most settings are made in
:guilabel:`Admin Tools > Settings > Extension Configuration > myra_cloud_connector`.

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


Myra Settings
-------------

Myra API setup

Myra API Endpoint
~~~~~~~~~~~~~~~~~

The Myra Cloud API endpoint for every request.

Myra API Key
~~~~~~~~~~~~

A Myra Cloud User API key (the user needs permissions to clear the domain cache).

Myra API Secret
~~~~~~~~~~~~~~~

The matching secret for the API key.

TYPO3 Site Settings
~~~~~~~~~~~~~~~~~~~

To link TYPO3 with Myra Cloud, it's necessary to announce the used Myra Cloud Domains for a TYPO3 Site Entity.
This can be done in :guilabel:`Site Management > Edit [SiteXYZ] > Myra Cloud > Myra Domain List`.

The configuration field accepts a comma separated list of all Myra domains. These are the domains which are
supported by this particular site. You can find the correct names in the
`Myra Cloud Backend <https://dashboard.myracloud.com>`__.

..  figure:: /img/myra_websites.png
    :alt: Myra Backend Website List
