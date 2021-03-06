<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

/* Set up the tt_content fields for the two frontend plugins */
/* DO NOT MOVE TO Configuration/TCA/Overrides/tt_content.php! */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['wec_map_pi1']='layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['wec_map_pi2']='layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['wec_map_pi3']='layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['wec_map_pi1']='pi_flexform';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['wec_map_pi2']='pi_flexform';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['wec_map_pi3']='pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_wecmap_external');

if (TYPO3_MODE == 'BE') {
    /**
     * Registers a Backend Module
     */
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'JBartels.wec_map',
        'web',	 	// Make module a submodule of 'web'
        'FEUserMap',	// Submodule key
        '',			// Position
        [
            'FeUserMapBackendModule' =>
                'show,alert',
        ],
        [
            'access' => 'admin',
            'icon'   => 'EXT:wec_map/ext_icon.gif',
            'labels' => 'LLL:EXT:wec_map/Resources/Private/Languages/Backend/FEUserMap/locallang.xlf',
        ]
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'JBartels.wec_map',
        'tools',	 	// Make module a submodule of 'admin tools'
        'MapAdministration',	// Submodule key
        '',			// Position
        [
            'MapAdministrationBackendModule' =>
                'geocode,batch,download,apikey,editCacheEntry,saveCacheEntry,closeCacheEntry,deleteCacheEntry,deleteCache',
        ],
        [
            'access' => 'admin',
            'icon'   => 'EXT:wec_map/ext_icon.gif',
            'labels' => 'LLL:EXT:wec_map/Resources/Private/Languages/Backend/MapAdministration/locallang.xlf',
        ]
    );
}
