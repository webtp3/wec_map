<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

/* Define the address related fields for a frontend user */
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('sr_feuser_register')) {
    $GLOBALS['TCA']['fe_users']['ctrl']['EXT']['wec_map'] = [
        'isMappable' => 1,
        'addressFields' => [
            'street' => 'address',
            'city' => 'city',
            'state' => 'zone',
            'zip' => 'zip',
            'country' => 'static_info_country',
        ],
    ];
} else {
    $GLOBALS['TCA']['fe_users']['ctrl']['EXT']['wec_map'] = [
        'isMappable' => 1,
        'addressFields' => [
            'street' => 'address',
            'city' => 'city',
            'state' => '',
            'zip' => 'zip',
            'country' => 'country',
        ],
    ];
}

/* If we want to show a map in frontend user records, add it to the TCA */
if (\JBartels\WecMap\Utility\Backend::getExtConf('feUserRecordMap')) {
    $mapTCA = [
        'tx_wecmap_map' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:wec_map/Resources/Private/Languages/locallang_db.xlf:berecord_maplabel',
            'config' => [
                'type' => 'user',
                'userFunc' => 'JBartels\\WecMap\\Utility\\Backend->drawMap',
            ],
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $mapTCA, 1);
    $GLOBALS['TCA']['fe_users']['interface']['showRecordFieldList'] .= ',tx_wecmap_map';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users', '--div--;LLL:EXT:wec_map/Resources/Private/Languages/locallang_db.xlf:berecord_maplabel,tx_wecmap_map');
}

/* If we want to show the geocoding status in frontend user records, add it to the TCA */
if (\JBartels\WecMap\Utility\Backend::getExtConf('geocodingStatus')) {
    $geocodeTCA = [
        'tx_wecmap_geocode' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:wec_map/Resources/Private/Languages/locallang_db.xlf:berecord_geocodelabel',
            'config' => [
                'type' => 'user',
                'userFunc' => 'JBartels\\WecMap\\Utility\\Backend->checkGeocodeStatus',
            ],
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $geocodeTCA, 1);
    $GLOBALS['TCA']['fe_users']['interface']['showRecordFieldList'] .= ',tx_wecmap_geocode';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users', 'tx_wecmap_geocode');
}
