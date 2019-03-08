<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JBartels\WecMap\Module\MapAdministration;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;

class RecordHandler
{
    public $itemsPerPage = 75;

    /**
     * Displays the table with cache records
     *
     * @return String
     **/
    public function displayTable()
    {
        global $LANG;

        $limit = null;
        // Select rows:
        $displayRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_wecmap_cache', '', 'address', 'address', $limit);

        //		$iconEdit = IconFactory::getIcon('actions-document-open',Icon::SIZE_SMALL
        //		);
        //		$iconSave = IconFactory::getIcon('actions-document-save', Icon::SIZE_SMALL
        //		);
        //		$iconCancel = IconFactory::getIcon('actions-document-close', Icon::SIZE_SMALL
        //		);
//
        //		$iconDelete = IconFactory::getIcon('actions-edit-delete', Icon::SIZE_SMALL
        //		);

        $tablebody = '';
        foreach ($displayRows as $row) {

            // Add icon/title and ID:
            $cells = [];

            $cells[] = '<td class="address">' . htmlspecialchars($row['address']) . '</td>';

            $cells[] = '<td class="latitude">' . htmlspecialchars($row['latitude']) . '</td>';
            $cells[] = '<td class="longitude">' . htmlspecialchars($row['longitude']) . '</td>';

            $cells[] = '<td class="editButton">' .
                         '<span class="editButton">' . $iconEdit . '</span>' .
                         '<span class="saveButton" style="display: none;">' . $iconSave . '</span>' .
                         '<span class="cancelButton" style="display: none;">' . $iconCancel . '</span>' .
                       '</td>';
            $cells[] = '<td class="deleteButton"><span class="deleteButton">' . $iconDelete . '</span></td>';

            // Compile Row:
            $tablebody .= '<tr id="item_' . $row['address_hash'] . '" data-cacheid="' . $row['address_hash'] . '" class="address">' . implode('', $cells) . '</tr>';
        }

        $output = $this->getTotalCountHeader()
                . '<br />'
                ;

        // Create header:
        $headerCells = [];
        $headerCells[] = '<th>' . $LANG->getLL('address') . '</th>';
        $headerCells[] = '<th style="width: 6em;">' . $LANG->getLL('latitude') . '</th>';
        $headerCells[] = '<th style="width: 6em;">' . $LANG->getLL('longitude') . '</th>';
        $headerCells[] = '<th colspan="2">Actions</th>';

        $output .= '<table id="tx-wecmap-cache">'
                 . '<thead><tr>' . implode('', $headerCells) . '</tr></thead>'
                 . '<tbody>' . $tablebody . '</tbody>'
                 . '</table>'
                 . '<div id="noRecords" style="display:none">' . $LANG->getLL('noRecords') . '</div>'
                 ;

        return $output;
    }

    /**
     * Shows a search box to filter cache records
     *
     * @return String
     **/
    public function displaySearch()
    {
        global $LANG;

        //$iconFilter = IconFactory::getIcon( 'actions-document-save-close', Icon::SIZE_SMALL );

        $content = '<div><input id="recordSearchbox" type="text" placeholder="' . $LANG->getLL('searchFilter') . '" size="20"/><button id="resetSearchboxButton" style="display: none;">' . $LANG->getLL('clearFilter') . '</button></div>';
        return $content;
    }

    /**
     * Returns the header part that allows to delete all records and shows the
     * total number of records
     *
     * @return String
     **/
    public function getTotalCountHeader()
    {
        global $LANG;
        //		$iconDelete = IconFactory::getIcon('actions-edit-delete', array(
        //			'title' => $LANG->getLL('deleteCache'),
        //			'alt' => $LANG->getLL('deleteCache')
        //			)
        //		);
        $iconDelete = 'delete';
        $content = $LANG->getLL('totalCachedAddresses') .
            ': <strong><span id="recordCount">?</span></strong><span id="deleteCache">' . $iconDelete . '</span>';

        return $content;
    }

    public function linkSelf($addParams)
    {
        return htmlspecialchars('index.php?id=' . $this->pObj->id . '&showLanguage=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showLanguage')) . $addParams);
    }
}

//if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/mod1/class.tx_wecmap_recordhandler.php'])	{
//	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/mod1/class.tx_wecmap_recordhandler.php']);
//}
