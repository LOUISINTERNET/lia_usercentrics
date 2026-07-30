<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'LIA Usercentrics',
    'description' => 'This TYPO3 extension integrates a cookie banner for managing consent for external services such as analytics, social media, etc. It ensures that external sources (like YouTube videos, Vimeo, or other integrated services) are blocked by default until the user grants consent for their use.',
    'category' => 'fe',
    'author' => 'LOUIS TYPO3 Developers',
    'author_company' => 'LOUIS INTERNET',
    'author_email' => 'info@dev.louis.info',
    'state' => 'stable',
    'version' => '1.1.3',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
