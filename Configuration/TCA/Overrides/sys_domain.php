<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$columns = [
    'browserKey' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:wec_map/Resources/Private/Languages/locallang_db.xlf:sys_domain.browserKey',
        'config' => [
            'type' => 'input',
            'size' => '32',
            'max' => '50',
        ]
    ],
    'staticKey' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:wec_map/Resources/Private/Languages/locallang_db.xlf:sys_domain.staticKey',
        'config' => [
            'type' => 'input',
            'size' => '32',
            'max' => '50',
        ]
    ],
    'serverKey' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:wec_map/Resources/Private/Languages/locallang_db.xlf:sys_domain.serverKey',
        'config' => [
            'type' => 'input',
            'size' => '32',
            'max' => '50',
        ]
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_domain', $columns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
    'sys_domain',
    'LLL:EXT:wec_map/Resources/Private/Languages/locallang_db.xlf:sys_domain.GoogleAPI',
    'browserKey, staticKey, serverKey',
    'after:forced'
);

// Make fields visible in the TCEforms:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'sys_domain',
    '--div--;LLL:EXT:wec_map/Resources/Private/Languages/locallang_db.xlf:sys_domain.GoogleAPITab, browserKey, staticKey, serverKey',
    '', // List of specific types to add the field list to. (If empty, all type entries are affected)
    '' // Insert fields before (default) or after one, or replace a field
);
