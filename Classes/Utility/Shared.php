<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JBartels\WecMap\Utility;

/**
 * General purpose class for the WEC Map extension.  This class
 * provides shared methods used by other classes
 *
 */
class Shared
{
    public static function render($data, $conf, $table = '')
    {
        $local_cObj =  \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class); // Local cObj.
        $local_cObj->start($data, $table);
        $output = $local_cObj->cObjGet($conf);
        return $output;
    }

    public static function cObjGet($setup, &$cObj, $addKey='')
    {
        if (is_array($setup)) {
            $sKeyArray = $setup;
            $content ='';

            foreach ($sKeyArray as $theKey => $theValue) {
                if (!strstr($theKey, '.')) {
                    $conf=$setup[$theKey . '.'];
                    $content.=$cObj->cObjGetSingle($theValue, $conf, $addKey . $theKey);	// Get the contentObject
                }
            }
            return $content;
        }
    }

    public static function listQueryFromCSV($field, $values, $table, $mode = 'AND')
    {
        $where = ' AND (';
        $csv = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $values);
        for ($i=0; $i < count($csv); $i++) {
            if ($i >= 1) {
                $where .= ' ' . $mode . ' ';
            }
            $where .= $GLOBALS['TYPO3_DB']->listQuery($field, $csv[$i], $table);
        }

        return $where . ')';
    }

    public static function getAddressField($table, $field)
    {
        return $GLOBALS['TCA'][$table]['ctrl']['EXT']['wec_map']['addressFields'][$field];
    }

    public static function getLatLongField($table, $field)
    {
        return $GLOBALS['TCA'][$table]['ctrl']['EXT']['wec_map']['latlongFields'][$field];
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_shared.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_shared.php']);
}
