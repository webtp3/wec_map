<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

/*
** Add the frontend plugins
**
** Trick: call addTypoScript() to setup a userFunc with namespace. addPItoST43() adds only a non-namespaced entry.
*/
//\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
//    'JBartels.WecMap',
//    'Pi3',
//    [
//        'Plugin' => 'pi3'
//    ],
//    // non-cacheable actions
//    [
//    ]
//);
//\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
//    'JBartels.WecMap',
//    'Pi2',
//    [
//        'Plugin' => 'pi2'
//    ],
//    // non-cacheable actions
//    [
//    ]
//);
//\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
//    'JBartels.WecMap',
//    'Pi1',
//    [
//        'Plugin' => 'pi1'
//    ],
//    // non-cacheable actions
//    [
//    ]
//);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43('wec_map','Classes/Plugin/SimpleMap/SimpleMap.php','_pi1','list_type',0);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('wec_map', 'setup', 'plugin.tx_wecmap_pi1.userFunc = JBartels\\WecMap\\Plugin\\SimpleMap\\SimpleMap->main');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43('wec_map','Classes/Plugin/FEUserMap/FEUserMap.php','_pi2','list_type',0);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('wec_map', 'setup', 'plugin.tx_wecmap_pi2.userFunc = JBartels\\WecMap\\Plugin\\FEUserMap\\FEUserMap->main');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43('wec_map','Classes/Plugin/DataTableMap/DataTableMap.php','_pi3','list_type',0);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('wec_map', 'setup', 'plugin.tx_wecmap_pi3.userFunc = JBartels\\WecMap\\Plugin\\DataTableMap\\DataTableMap->main');

/*
** add New CE-wizard elements
*/
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:wec_map/Configuration/PageTS/NewContentElementWizard.ts">'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][__CLASS__] = \JBartels\WecMap\Utility\Backend::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][__CLASS__] = \JBartels\WecMap\Utility\Backend::class;


/* Add the Google geocoding service */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService('wec_map','geocode','JBartels\\WecMap\\GeocodeService\\Google',
	array(

		'title' => 'Google Maps Address Lookup API V3',
		'description' => '',

		'subtype' => '',

		'available' => TRUE,
		'priority' => 100,
		'quality' => 100,

		'os' => '',
		'exec' => '',

		'className' => 'JBartels\\WecMap\\GeocodeService\\Google'
	)
);

?>