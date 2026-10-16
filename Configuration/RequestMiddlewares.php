<?php

return [
    'frontend' => [
        'lia/lia-usercentrics/bypass-cache' => [
            'target' => \LIA\LiaUsercentrics\Middleware\BypassCacheMiddleware::class,
            'after' => [
                'typo3/cms-frontend/page-argument-validator',
            ],
            'before' => [
                'typo3/cms-frontend/tsfe',
            ],
        ],
    ],
];
