<?php

return [
    'external_cache_clear' => [
        'path' => '/cps_myra_cloud/external/clear_cache_page',
        'target' => CPSIT\CpsMyraCloud\Controller\ExternalClearCacheController::class . '::clearPageCache',
    ]
];
