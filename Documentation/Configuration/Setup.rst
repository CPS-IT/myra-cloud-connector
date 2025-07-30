===============
Extension Setup
===============

Most settings are made in

``Admin Tools > Settings > Extension Configuration > myra_cloud_connector``

Use of environment variables
---------------------

Configuration values can be set as text or parsed from environment values

.. image:: ../img/env_inject.png
   :alt: Extension configuration Settings


Every config that start with ``ENV=`` will be parsed and the provided environment variable will be used.

Example syntax:

``ENV=MYRA_API_KEY # results in getenv('MYRA_API_KEY')``


Myra Settings
-------------

Myra API setup

Myra API Endpoint
~~~~~~~~~~~~~~~~~

The Myra Cloud API endpoint for every request.

Myra API Key
~~~~~~~~~~~~

a Myra Cloud User API KEY (who has the permissions to clear the domain).

Myra API Secret
~~~~~~~~~~~~~~~

the matching Secret for the API-key.

TYPO3 Site Settings
~~~~~~~~~~~~~~~~~~~

To link TYPO3 with Myra Cloud it's necessary to announce the used Myra Cloud Domains for a TYPO3 Site Entity.

``Site Management > Edit [SiteXYZ] > Myra Cloud > Myra Domain List``

This is a comma separated list of all Myra domains, this particular site supports. You can find the correct names in
`Myra Cloud Backend <https://dashboard.myracloud.com>`__.

.. image:: ../img/myra_websites.png
    :alt: Myra Backend Website List
