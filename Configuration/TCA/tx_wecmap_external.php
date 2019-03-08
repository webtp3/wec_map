<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

return [
    'ctrl' => [
        'title' => 'LLL:EXT:wec_map/Resources/Private/Languages/locallang_db.xlf:tx_wecmap_external',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'iconfile' => 'EXT:wec_map/Resources/Public/Images/icon_tx_wecmap_external_resource.gif',
    ],
    'interface' => [
        'showRecordFieldList' => 'title,url'
    ],
    'columns' => [
        'title' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:wec_map/Resources/Private/Languages/locallang_db.xlf:tx_wecmap_external.title',
            'config' => [
                'type' => 'input',
                'size' => '32',
                'max' => '128',
            ]
        ],
        'url' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:wec_map/Resources/Private/Languages/locallang_db.xlf:tx_wecmap_external.url',
            'config' => [
                'type' => 'input',
                'size' => '32',
                'max' => '128',
                'wizards' => [
                        'link' => [
                                'type' => 'popup',
                                'title' => 'Link',
                                'icon' => 'actions-wizard-link',
                                'module' => [
                                    'name' => 'wizard_link',
                                ],
                                'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
                                'params' => [
                                    'allowedExtensions' => 'kml, xml, kmz',
                                    'blindLinkOptions' => 'folder, mail, page, spec, url'
                                ]
                        ],

                ]
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'title, url']
    ],
    'palettes' => [
        '1' => ['showitem' => 'title, url'],
    ],
];
