<?php

defined('TYPO3_MODE') or die('Access denied.');

// Driver
\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Driver\DriverRegistry::class)->registerDriverClass(
    \HDNET\FalCelum\Driver\CelumDriver::class,
    \HDNET\FalCelum\Driver\CelumDriver::DRIVER_TYPE,
    'Celum',
    'FILE:EXT:' . \HDNET\FalCelum\Driver\CelumDriver::EXTENSION_KEY . '/Configuration/FlexForm/CelumDriverFlexForm.xml'
);

// Extractor
\TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance()->registerExtractionService(\HDNET\FalCelum\Index\Extractor::class);

// Caching
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][\HDNET\FalCelum\Cache::IDENTIFIER])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][\HDNET\FalCelum\Cache::IDENTIFIER] = [
        'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
        'options' => [
            'defaultLifetime' => \HDNET\FalCelum\Cache::DEFAULT_LIFE_TIME
        ],
    ];
}

// Logging (@todo Enable via Extension configuration)
$GLOBALS['TYPO3_CONF_VARS']['LOG']['HDNET']['FalCelum']['writerConfiguration'] = [
    \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
            'logFileInfix' => 'fal_celum',
        ]
    ]
];

