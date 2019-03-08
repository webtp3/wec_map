<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JBartels\WecMap\Module\MapAdministration;

/**
 * Performs autmated geocoding for any address information.
 *
 */
class BatchGeocode
{
    public $tables;
    public $geocodedAddresses;
    public $geocodeLimit;
    public $processedAddresses;

    /**
     * Default constructor.
     *
     * @return		none
     */
    public function __construct($limit=10)
    {
        $this->tables = [];
        $this->geocodedAddresses = 0;
        $this->processedAddresses = 0;
        $this->geocodeLimit = $limit;
    }

    /**
     * Adds a specific tables to the list of tables that should be geocoded.
     *
     * @param		string		The name of the table.
     * @return		none
     */
    public function addTable($table)
    {
        $this->tables[] = $table;
    }

    /**
     * Traverses the TCA and adds all mappable tables to the list of tables that
     * should be geocoded.
     *
     * @return		none
     */
    public function addAllTables()
    {
        foreach ($GLOBALS['TCA'] as $tableName => $tableContents) {
            if ($tableContents['ctrl']['EXT']['wec_map']['isMappable']) {
                $this->tables[] = $tableName;
            }
        }
    }

    /**
     * Main function to initiate geocoding of all address-related tables.
     *
     * @return		none
     */
    public function geocode()
    {
        if (is_array($this->tables)) {
            foreach ($this->tables as $table) {
                if ($this->stopGeocoding()) {
                    return;
                } else {
                    $this->geocodeTable($table);
                }
            }
        }
    }

    /**
     * Performs geocoding on an individual table.
     *
     * @param		string		Name of the table.
     * @return		none
     */
    public function geocodeTable($table)
    {
        $addressFields = [
            'street'  => \JBartels\WecMap\Utility\Shared::getAddressField($table, 'street'),
            'city'    => \JBartels\WecMap\Utility\Shared::getAddressField($table, 'city'),
            'state'   => \JBartels\WecMap\Utility\Shared::getAddressField($table, 'state'),
            'zip'     => \JBartels\WecMap\Utility\Shared::getAddressField($table, 'zip'),
            'country' => \JBartels\WecMap\Utility\Shared::getAddressField($table, 'country'),
        ];

        $where = '1=1' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);
        $result =  $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, $where);
        while ($row =  $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
            if ($this->stopGeocoding()) {
                return;
            } else {
                $this->geocodeRecord($row, $addressFields);
            }
        }
    }

    /**
     * Performs geocoding on an individual row.
     *
     * @param		array		The associative array of the record to be geocoded.
     * @param		array		The array mapping address elements to individual fields in the record.
     * @return		none
     */
    public function geocodeRecord($row, $addressFields)
    {
        $street  = $addressFields['street']  > '' ? $row[$addressFields['street']]  : '';
        $city    = $addressFields['city']    > '' ? $row[$addressFields['city']]    : '';
        $state   = $addressFields['state']   > '' ? $row[$addressFields['state']]   : '';
        $zip     = $addressFields['zip']     > '' ? $row[$addressFields['zip']]     : '';
        $country = $addressFields['country'] > '' ? $row[$addressFields['country']] : '';

        // increment total count
        $this->processedAddresses++;
        \JBartels\WecMap\Utility\Cache::lookupWithCallback($street, $city, $state, $zip, $country, false, $this);
    }

    /**
     * Callback function for tx_wecmap_cache::lookup().  Called when a lookup
     * is not cached and must use external geocoding services. Increments an
     * internal counter of how many external lookups we've made.
     *
     * @return		none
     */
    public function callback_lookupThroughGeocodeService()
    {
        $this->geocodedAddresses++;
    }

    /**
     * Utility function to determine whether batch geocoding should be stopped.
     *
     * @return		bool		True/false whethr batch geocoding should be stopped.
     */
    public function stopGeocoding()
    {
        if ($this->geocodedAddresses >= $this->geocodeLimit) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Getter function for the total number of addresses processed.
     *
     * @return		The total number of addresses processed.  This includes both
     *				cached and non-cached.
     */
    public function getProcessedAddresses()
    {
        return $this->processedAddresses;
    }

    /**
     * Getter function for the total number of addresses geocoded.
     *
     * @return		The total number of addresses geocoded by external services.
     *				This does not include cached addresses.
     */
    public function getGeocodedAddresses()
    {
        return $this->geocodedAddresses;
    }

    /**
     * Count of all records containing address-related data.
     *
     * @return		int		The count of all records with addresses.
     */
    public function getRecordCount()
    {
        $recordCount = 0;

        if (is_array($this->tables)) {
            foreach ($this->tables as $table) {
                $where = '1=1' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);
                $result =  $GLOBALS['TYPO3_DB']->exec_SELECTquery('COUNT(*)', $table, $where);
                $row =  $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
                $recordCount += $row['COUNT(*)'];
            }
        }

        return $recordCount;
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_batchgeocode.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_batchgeocode.php']);
}
