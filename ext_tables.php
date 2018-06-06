<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
$_EXTKEY = "wec_map";
    /***************
     * Make the extension configuration accessible
     */
    if (class_exists('TYPO3\CMS\Core\Configuration\ExtensionConfiguration')) {
        $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        );
        $tp3modsConfig = $extensionConfiguration->get('wec_map');
    } else {
        // Fallback for CMS8
        // @extensionScannerIgnoreLine
        $tp3modsConfig = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wec_map'];
        if (!is_array($tp3modsConfig)) {
            $tp3modsConfig = unserialize($tp3modsConfig);
        }
    }
    if (!is_array($tp3modsConfig)) {
        $tp3modsConfig = unserialize($tp3modsConfig);
    }

    if (TYPO3_MODE == 'BE' && !class_exists('TYPO3\CMS\Core\Configuration\ExtensionConfiguration')) {
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'JBartels.WecMap',
                'tools', // Make module a submodule of 'tools'
                'txwecmapM1', // Submodule key
                '', // Position
                [
                    'MapAdministration' => 'index, list, show, edit, update',
                ],
                [
                    'access' => 'user,group',
                    'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/Module.svg',
                    'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf',
                ]
            );

            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'JBartels.WecMap',
                'tools', // Make module a submodule of 'tools'
                'txwecmapM2', // Submodule key
                '', // Position
                [
                    'FEUserMap' => 'index, list, show, edit, update',
                ],
                [
                    'access' => 'user,group',
                    'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/Module.svg',
                    'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf',
                ]
            );
    }
    else if (TYPO3_MODE=='BE')    {
        /* Add the backend modules */
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('tools','txwecmapM1','',\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wec_map').'Classes/Module/MapAdministration/');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('tools','txwecmapM2','',\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wec_map').'Classes/Module/FEUserMap/');
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
?>