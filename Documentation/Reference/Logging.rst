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

A page clear for a page that does not exist, or for a deleted page that was
not public, clears nothing and is logged with level ``warning`` (component
:php:`CPSIT.MyraCloudConnector.Service.ExternalCacheService`). Up to
3.3 / 4.1, it cleared the start page (``/``) of every site instead.

After a network error (timeout, no connection, server error) the remaining
Myra requests of the same request are skipped instead of waiting for the
timeout again, for example when a save clears many pages. They are logged
in sys_log as not successful.


..  figure:: /img/log-example.jpg
    :alt: View of TYPO3 system log, showing a Myra Clear Cache log entry
