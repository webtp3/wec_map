<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JBartels\WecMap\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * General purpose backend class for the WEC Map extension.  This class
 * provides user functions for displaying geocode status and maps within
 * TCEForms.
 *
 */
class Backend
{
    public static function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, &$reference)
    {
        $tca = &$GLOBALS['TCA'][$table]['ctrl']['EXT']['wec_map'];
        $isMappable = $tca['isMappable'];

        if ($isMappable) {
            if ($tca['addressFields']) {
                /* Get the names of the fields from the TCA */
                $streetField  = \JBartels\WecMap\Utility\Shared::getAddressField($table, 'street');
                $cityField    = \JBartels\WecMap\Utility\Shared::getAddressField($table, 'city');
                $stateField   = \JBartels\WecMap\Utility\Shared::getAddressField($table, 'state');
                $zipField     = \JBartels\WecMap\Utility\Shared::getAddressField($table, 'zip');
                $countryField = \JBartels\WecMap\Utility\Shared::getAddressField($table, 'country');

                /* Get the row that we're saving */
                $row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $id);

                /* @todo	Eliminate double save */
                self::drawGeocodeStatus($row[$streetField], $row[$cityField], $row[$stateField], $row[$zipField], $row[$countryField]);
            } elseif ($tca['latlongFields']) {
                /* Get the names of the fields from the TCA */
                $latField  = \JBartels\WecMap\Utility\Shared::getLatLongField($table, 'lat');
                $longField    = \JBartels\WecMap\Utility\Shared::getLatLongField($table, 'long');

                /* Get the row that we're saving */
                $row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $id);

                /* @todo	Eliminate double save */
                self::drawLatlongStatus($row[$latField], $row[$longField]);
            }
        }
    }

    public static function processDatamap_preProcessFieldArray(array &$incomingFieldArray, $table, $id, \TYPO3\CMS\Core\DataHandling\DataHandler &$reference)
    {
        $tca = $GLOBALS['TCA'][$table]['ctrl']['EXT']['wec_map'];
        $isMappable = $tca['isMappable'];

        if ($isMappable) {
            if ($tca['latlongFields']) {
                /* Grab the lat and long that were posted */
                $newlat = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wec_map_lat');
                $newlong = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wec_map_long');

                $origlat = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wec_map_original_lat');
                $origlong = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wec_map_original_long');

                /* If the lat/long changed, then insert a new entry into the cache or update it. */
                if ((($newlat != $origlat) or ($newlong != $origlong)) and (!empty($newlat) && !empty($newlong)) and (is_numeric($newlat) && is_numeric($newlong))) {
                    /* Get the names of the fields from the TCA */
                    $latField  = \JBartels\WecMap\Utility\Shared::getLatLongField($table, 'lat');
                    $longField = \JBartels\WecMap\Utility\Shared::getLatLongField($table, 'long');

                    $incomingFieldArray[$latField] = $newlat;
                    $incomingFieldArray[$longField] = $newlong;
                }
            }
        }
    }

    /**
     * Checks the geocoding status for the current record.  This function is
     * mainly responsible for taking backend record data and handing it to
     * drawGeocodeStatus().
     *
     * @param	array	Array with information about the current field.
     * @param	object	Parent object.  Instance of t3lib_tceforms.
     * @return	string	HTML output of current geocoding status and editing form.
     */
    public static function checkGeocodeStatus($PA, &$fobj)
    {
        // if geocoding status is disabled, return
        if (!self::getExtConf('geocodingStatus')) {
            return;
        }

        $street  = self::getFieldValue('street', $PA);
        $city    = self::getFieldValue('city', $PA);
        $state   = self::getFieldValue('state', $PA);
        $zip     = self::getFieldValue('zip', $PA);
        $country = self::getFieldValue('country', $PA);

        return self::drawGeocodeStatus($street, $city, $state, $zip, $country);
    }

    /**
     * Checks the geocoding status for the current record.  This function is
     * mainly responsible for taking backend record data and handing it to
     * drawLatlongStatus().
     *
     * @param	array	Array with information about the current field.
     * @param	object	Parent object.  Instance of t3lib_tceforms.
     * @return	string	HTML output of current geocoding status and editing form.
     */
    public static function checkLatLongStatus($PA, &$fobj)
    {
        // if geocoding status is disabled, return
        if (!self::getExtConf('geocodingStatus')) {
            return;
        }

        $lat  = self::getFieldValue('lat', $PA);
        $long = self::getFieldValue('long', $PA);

        return self::drawLatlongStatus($lat, $long);
    }

    /**
     * Checks the goecoding status for the current FlexForm.  This function is
     * mainly responsible for taking FlexForm data and handing it to
     * drawGeocodeStatus().
     *
     * @param	array	Array with information about the current FlexForm.
     * @param	object	Parent object.  Instance of t3lib_tceforms.
     * @return	string	HTML output of current geocoding status and editing form.
     * @todo	Does our method of digging into FlexForms mess up localization?
     */
    public static function checkGeocodeStatusFF($PA, &$fobj)
    {

        // if geocoding status is disabled, return
        if (!self::getExtConf('geocodingStatus')) {
            return;
        }

        $street  = self::getFieldValueFromFF('street', $PA);
        $city    = self::getFieldValueFromFF('city', $PA);
        $state   = self::getFieldValueFromFF('state', $PA);
        $zip     = self::getFieldValueFromFF('zip', $PA);
        $country = self::getFieldValueFromFF('country', $PA);

        return self::drawGeocodeStatus($street, $city, $state, $zip, $country);
    }

    /**
     * Checks the geocoding status of the address and displays an editing form.
     *
     * @param	string	Street portion of the address.
     * @param	string	City portion of the address.
     * @param	string	State portion of the address.
     * @param	string	ZIP code portion of the address.
     * @param	string	Country portion of the address.
     * @return	string	HTML output of current geocoding status and editing form.
     */
    public static function drawGeocodeStatus($street, $city, $state, $zip, $country)
    {
        global $LANG;
        $LANG->includeLLFile('EXT:wec_map/Resources/Private/Languages/locallang_db.xlf');

        /* Normalize the address before we try to insert it or anything like that */
        \JBartels\WecMap\Utility\Cache::normalizeAddress($street, $city, $state, $zip, $country);

        // if there is no info about the user, return different status
        if (!$city) {
            return $LANG->getLL('geocodeNoAddress');
        }

        /* Grab the lat and long that were posted */
        $newlat = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wec_map_lat');
        $newlong = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wec_map_long');

        $origlat = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wec_map_original_lat');
        $origlong = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wec_map_original_long');

        /* If the new lat/long are empty, delete our cached entry */
        if (empty($newlat) && empty($newlong) && !empty($origlat) && !empty($origlong)) {
            \JBartels\WecMap\Utility\Cache::delete($street, $city, $state, $zip, $country);
        }

        /* If the lat/long changed, then insert a new entry into the cache or update it. */
        if ((($newlat != $origlat) or ($newlong != $origlong)) and (!empty($newlat) && !empty($newlong)) and (is_numeric($newlat) && is_numeric($newlong))) {
            \JBartels\WecMap\Utility\Cache::insert($street, $city, $state, $zip, $country, $newlat, $newlong);
        }

        /* Get the lat/long and status from the geocoder */
        $latlong = \JBartels\WecMap\Utility\Cache::lookup($street, $city, $state, $zip, $country);
        $status = \JBartels\WecMap\Utility\Cache::status($street, $city, $state, $zip, $country);

        switch ($status) {
            case -1:
                $status = $LANG->getLL('geocodeFailed');
                break;
            case 0:
                $status = $LANG->getLL('geocodeNotPerformed');
                break;
            case 1:
                $status = $LANG->getLL('geocodeSuccessful');
                break;
        }

        $form = '<label for="wec_map_lat">' . $LANG->getLL('latitude') . '</label> <input id="wec_map_lat" name="wec_map_lat" value="' . htmlspecialchars($latlong['lat']) . '" />
				 <label for="wec_map_long">' . $LANG->getLL('longitude') . '</label>  <input id="wec_map_long" name="wec_map_long" value="' . htmlspecialchars($latlong['long']) . '" />
				 <input type="hidden" name="wec_map_original_lat" value="' . htmlspecialchars($latlong['lat']) . '" />
				 <input type="hidden" name="wec_map_original_long" value="' . htmlspecialchars($latlong['long']) . '" />';

        return '<p>' . $status . '</p><p>' . $form . '</p>';
    }

    /**
     * displays an editing form.
     *
     * @param	string	Latitude portion of the address.
     * @param	string	Longitude portion of the address.
     * @return	string	HTML output of current geocoding status and editing form.
     */
    public static function drawLatlongStatus($lat, $long)
    {
        global $LANG;
        $LANG->includeLLFile('EXT:wec_map/Resources/Private/Languages/locallang_db.xlf');

        /* Grab the lat and long that were posted */
        $newlat = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wec_map_lat');
        $newlong = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wec_map_long');

        $origlat = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wec_map_original_lat');
        $origlong = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wec_map_original_long');

        $form = '<input type="hidden" id="wec_map_lat" name="wec_map_lat" value="' . htmlspecialchars($lat) . '" />
				 <input type="hidden" id="wec_map_long" name="wec_map_long" value="' . htmlspecialchars($long) . '" />
				 <input type="hidden" name="wec_map_original_lat" value="' . htmlspecialchars($lat) . '" />
				 <input type="hidden" name="wec_map_original_long" value="' . htmlspecialchars($long) . '" />';

        return '<p>' . $form . '</p>';
    }

    /**
     * Draws a backend map.
     * @param		array		Array with information about the current field.
     * @param		object		Parent object.  Instance of t3lib_tceforms.
     * @return		string		HTML to display the map within a backend record.
     */
    public static function drawMap($PA, $fobj)
    {
        $width = '400';
        $height = '400';

        $street  = self::getFieldValue('street', $PA);
        $city    = self::getFieldValue('city', $PA);
        $state   = self::getFieldValue('state', $PA);
        $zip     = self::getFieldValue('zip', $PA);
        $country = self::getFieldValue('country', $PA);

        $description = $street . '<br />' . $city . ', ' . $state . ' ' . $zip . '<br />' . $country;
        $map =  \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JBartels\WecMap\MapService\Google\Map::class, $apiKey, $width, $height);
        $marker = $map->addMarkerByAddress($street, $city, $state, $zip, $country, '<h1>Address</h1>', $description);
        // enable dragging to correct lat/long interactively
        if ($marker) {
            $marker->setDraggable(true);
        }

        // add some default controls to the map
        $map->addControl('largeMap');
        $map->addControl('scale');
        $map->addControl('mapType');
        $map->enableDirections(true);

        $content = $map->drawMap();

        return $content;
    }

    /**
     * Draws a backend map.
     * @param		array		Array with information about the current field.
     * @param		object		Parent object.  Instance of t3lib_tceforms.
     * @return		string		HTML to display the map within a backend record.
     */
    public static function drawLatLongMap($PA, $fobj)
    {
        $width = '400';
        $height = '400';

        $lat  = self::getFieldValue('lat', $PA);
        $long = self::getFieldValue('long', $PA);

        $description = $lat . ',' . $long;

        $apiKey = '';
        /** @var \tx_wecmap_map_google $map */
        $map =  \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JBartels\WecMap\MapService\Google\Map::class, $apiKey, $width, $height);
        $marker = $map->addMarkerByLatLong($lat, $long, $description);
        // enable dragging to correct lat/long interactively
        if ($marker) {
            $marker->setDraggable(true);
        }
        // add some default controls to the map
        $map->addControl('largeMap');
        $map->addControl('scale');
        $map->addControl('mapType');
        $map->enableDirections(true);

        $content = $map->drawMap();
        return $content;
    }

    /**
     * Checks the TCA for address mapping rules and returns the address value.
     * If a mapping rule is defined, this tells us what field contains address
     * related information.  If no rules are defined, we pick default fields
     * to use.
     *
     * @param	string	The portion of the address we're trying to map.
     * @param	array	Array of field related data.
     * @return	string	The specified portion of the address.
     * @todo			Refactor this to use getFieldNameForTable().
     */
    public static function getFieldValue($key, $PA)
    {
        $table = $PA['table'];

        $row = $PA['row'];

        /* If the address mapping array has a mapping for this address, use it */
        $addressFields = $PA['fieldConf']['config']['params']['addressFields'];
        if (isset($addressFields[$key])) {
            $fieldName = $addressFields[$key];
        } else {
            /* If the address mapping array has a mapping for this lat/long, use it */
            $latlongFields = $PA['fieldConf']['config']['params']['latlongFields'];
            if (isset($latlongFields[$key])) {
                $fieldName = $latlongFields[$key];
            } else {
                /* If the ctrl section of the TCA has a name, use it */
                $ctrlAddressFields = $GLOBALS['TCA'][$table]['ctrl']['EXT']['wec_map']['addressFields'];
                if (isset($ctrlAddressFields[$key])) {
                    $fieldName = \JBartels\WecMap\Utility\Shared::getAddressField($table, $key);
                } else {
                    /* Otherwise, use the default name */
                    $fieldName = $key;
                }
            }
        }

        /* If the source data has a value for the address field, grab it */
        if (isset($row[$fieldName])) {
            $value = $row[$fieldName];
        } else {
            /* Otherwise, use an empty string */
            $value = '';
        }

        return $value;
    }

    /**
     * Checks the FlexForm for address mapping rules and returns the address value.
     * If a mapping rule is defined, this tells us what field contains address
     * related information.  If no rules are defined, we pick default fields
     * to use.
     *
     * @param	string	The portion of the address we're trying to map.
     * @param	array	Array of field related data.
     * @return	string	The specified portion of the address.
     */
    public static function getFieldValueFromFF($key, $PA)
    {
        $flexForm = $PA['row']['pi_flexform'];
        if (is_array($flexForm)) {
            $flexForm = $flexForm['data']['default']['lDEF'];

            /* If the address mapping array has a map for this address, use it */
            $addressFields = $PA['fieldConf']['config']['params']['addressFields'];
            if (isset($addressFields[$key])) {
                $fieldName = $addressFields[$key];
            } else {
                $latlongFields = $PA['fieldConf']['config']['params']['latlongFields'];
                if (isset($latlongFields[$key])) {
                    $fieldName = $latlongFields[$key];
                } else {
                    $fieldName = $key;
                }
            }

            /* If the source data has a value for the addres field, grab it */
            if (isset($flexForm[$fieldName]['vDEF'])) {
                $value = $flexForm[$fieldName]['vDEF'];
            } else {
                $value = '';
            }
        } else {
            $value = '';
        }

        return $value;
    }

    /**
     * Gets extConf from TYPO3_CONF_VARS and returns the specified key.
     *
     * @param	string	The key to look up in extConf.
     * @return	mixed	The value of the specified key.
     */
    public static function getExtConf($key)
    {
        if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) >= 9000000) {
            $extConf = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('wec_map');
        } else {
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wec_map']);
        }

        /* Unserialize the TYPO3_CONF_VARS and extract the value using the parser */
        $valueArray = $extConf[ $key ];

        if (is_array($valueArray)) {
            $returnValue = $valueArray[0];
        } else {
            $returnValue = '';
        }

        return $returnValue;
    }

    /**
     * Returns a list of all mappable tables
     *
     * @return void
     **/
    public static function getMappableTables($config=null)
    {
        if (!isset($config)) {
            $config = [];
        }
        global $LANG;

        foreach ($GLOBALS['TCA'] as $table => $conf) {
            $isMappable = $conf['ctrl']['EXT']['wec_map']['isMappable'];
            if ($isMappable) {
                $title = $LANG->sL($conf['ctrl']['title']);
                $config['items'][] = [$title . ' (' . $table . ')', $table];
            }
        }
        return $config;
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_backend.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_backend.php']);
}
