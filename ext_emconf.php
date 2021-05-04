<?php

$EM_CONF['celum_connect_fal'] = [
    'title' => 'Celum (FAL)',
    'description' => 'Provides a smart Celum driver integration for TYPO3.',
    'category' => 'be',
    'version' => '0.8.0',
    'state' => 'stable',
    'author' => 'Tim Lochmüller',
    'author_email' => 'tl@hdnet.de',
    'author_company' => 'HDNET GmbH & Co. KG',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.0.99',
            'typo3' => '10.4.0-11.1.99',
        ],
    ],
];
