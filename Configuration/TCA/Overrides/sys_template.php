<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

/* Add static TS template for plugins */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('wec_map', 'Configuration/TypoScript/api/', 'WEC Map API');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('wec_map', 'Configuration/TypoScript/pi1/', 'WEC Simple Map');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('wec_map', 'Configuration/TypoScript/pi2/', 'WEC Frontend User Map');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('wec_map', 'Configuration/TypoScript/pi3/', 'WEC Table Map');
