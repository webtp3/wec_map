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

    public static function getListQueryFromCSV($field, $values, $queryBuilder, $mode = 'AND')
    {
        $where = [];
        $csv = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $values);
        foreach ($csv as $value) {
            $where[] = $queryBuilder->expr()->inSet($field, $queryBuilder->expr()->literal((string) $value));
        }
        if ($mode == 'OR') {
            return $queryBuilder->expr()->orX(...$where);
        }
        return $queryBuilder->expr()->andX(...$where);
    }

    public static function setOrderBy($queryBuilder, string $input)
    {
        // QueryHelper is marked "internal only" and may be deprecated in the future!
        foreach (\TYPO3\CMS\Core\Database\Query\QueryHelper::parseOrderBy((string)$orderBy) as $orderPair) {
            list($fieldName, $order) = $orderPair;
            $queryBuilder->addOrderBy($fieldName, $order);
        }
    }

    public static function setGroupBy($queryBuilder, string $input)
    {
        // QueryHelper is marked "internal only" and may be deprecated in the future!
        $queryBuilder->groupBy(...\TYPO3\CMS\Core\Database\Query\QueryHelper::parseGroupBy($groupBy));
    }

    public static function setLimit($queryBuilder, string $input)
    {
        if ($limitValues) {
            if ($limitValues->limit) {
                $queryBuilder->setMaxResult($limit);
            }
            if ($limitValues->offset) {
                $queryBuilder->setFirstResult($limit);
            }
        }

        $limitValues = [];
        $matchCount = preg_match('/^(?:LIMIT[[:space:]]+)?(\d+)(?:[[:space:]]*,[[:space:]]*(\d+))?$/i', trim($input), $limitValues);
        if ($matchCount == 1) {
            $queryBuilder->setMaxResult($input[1]);
        } elseif ($matchCount == 2) {
            $queryBuilder->setMaxResult($input[2]);
            $queryBuilder->setFirstResult($input[1]);
        } else {
            $matchCount = preg_match('/^(?:LIMIT[[:space:]]+)?(\d+)[[:space:]]+OFFSET[[:space:]]+(\d+))$/i', trim($input), $limitValues);
            if ($matchCount == 2) {
                $queryBuilder->setMaxResult($input[1]);
                $queryBuilder->setFirstResult($input[2]);
            }
        }
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
