<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('nn_address')) {
    $GLOBALS['TCA']['tx_nnaddress_domain_model_address']['ctrl']['EXT']['wec_map'] = [
        'isMappable' => 1,
        'addressFields' => [
            'street' => 'street',
            'city' => 'city',
            'state' => 'region',
            'zip' => 'zip',
            'country' => 'country',
        ],
    ];
}
