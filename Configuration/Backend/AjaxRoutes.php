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
    'txwecmapM1::deleteAll' => [
        'path' => '/txwecmapM1/deleteAll',
        'target' => \JBartels\WecMap\Module\MapAdministration\Ajax::class . '::ajaxDeleteAll'
    ],
    'txwecmapM1::deleteSingle' => [
        'path' => '/txwecmapM1/ajaxDeleteSingle',
        'target' => \JBartels\WecMap\Module\MapAdministration\Ajax::class . '::ajaxDeleteSingle'
    ],
    'txwecmapM1::saveRecord' => [
        'path' => '/txwecmapM1/ajaxSaveRecord',
        'target' => \JBartels\WecMap\Module\MapAdministration\Ajax::class . '::ajaxSaveRecord'
    ],
    'txwecmapM1::batchGeocode' => [
        'path' => '/txwecmapM1/ajaxBatchGeocode',
        'target' => \JBartels\WecMap\Module\MapAdministration\Ajax::class . '::ajaxBatchGeocode'
    ],
    'txwecmapM1::listRecords' => [
        'path' => '/txwecmapM1/ajaxListRecord',
        'target' => \JBartels\WecMap\Module\MapAdministration\Ajax::class . '::ajaxListRecord'
    ]
];
