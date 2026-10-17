..  _logging:

=======
Logging
=======

All ClearCache request will be added into the sys_log database, one entry per
cleared resource and host, also when several resources were sent in one bulk
request.

Failed requests to Myra (wrong credentials, unknown domain, network errors,
rejected resources) are additionally written to the TYPO3 logging framework
(PSR-3, level ``error``, component
:php:`CPSIT.MyraCloudConnector.Adapter.MyraApiAdapter`), with the reason given
by Myra.


..  figure:: /img/log-example.jpg
    :alt: View of TYPO3 system log, showing a Myra Clear Cache log entry
