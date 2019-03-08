<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JBartels\WecMap\Utility;

/**
 * Main address lookup class for the wec_map extension.  Looks up existing
 * values in cache tables or initiates service chain to perform a lookup.
 * Also provides basic administrative functions for managing entries in the
 * cache.
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class Cache
{
    const static_countries = 'static_countries';

    public static function lookup($street, $city, $state, $zip, $country, $forceLookup=false)
    {
        $fakeObject = null;
        return self::lookupWithCallback($street, $city, $state, $zip, $country, $forceLookup, $fakeObject);
    }

    /**
     * Looks up the latitude and longitude of a specified address. Cache tables
     * are searched first, followed by external service lookups.
     *
     * @param	string		The street address.
     * @param	string		The city name.
     * @param	string		The state name.
     * @param	string		This ZIP code.
     * @param	string		The country name.
     * @param	bool		Force a new lookup for address.
     * @return	array		Lat/long array for specified address.  Null if lookup fails.
     */
    public static function lookupWithCallback($street, $city, $state, $zip, $country, $forceLookup=false, &$pObj)
    {

        /* If we have enough address information, try to geocode. If not, return null. */
        if (self::isEmptyAddress($street, $city, $state, $zip, $country)) {
            $latlong = null;
        } else {
            /* Do some basic normalization on the address */
            self::normalizeAddress($street, $city, $state, $zip, $country);

            /* Look up the address in the cache table. */
            $latlong = self::find($street, $city, $state, $zip, $country);

            /* Didn't find a cached match */
            if (is_null($latlong)) {
                /* Intiate service chain to find lat/long */
                $serviceChain='';

                while (is_object($lookupObj =  \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('geocode', '', $serviceChain))) {
                    $serviceChain.=',' . $lookupObj->getServiceKey();
                    $latlong = $lookupObj->lookup($street, $city, $state, $zip, $country);

                    if (method_exists($pObj, 'callback_lookupThroughGeocodeService')) {
                        $pObj->callback_lookupThroughGeocodeService();
                    }

                    /* If we found a match, quit. Otherwise proceed to next best service */
                    if ($latlong) {
                        break;
                    }
                }

                /* Insert the lat/long into the cache.  */
                self::insert($street, $city, $state, $zip, $country, $latlong['lat'], $latlong['long']);
                $latlong['lat'] = trim($latlong['lat'], '0');
                $latlong['long'] = trim($latlong['long'], '0');
            }

            /* Return the lat/long, either from cache table for from fresh lookup */
            if ($latlong['lat'] == 0 and $latlong['long'] == 0) {
                $latlong = null;
            }
        }

        return $latlong;
    }

    /**
     * Performs basic normalize on the address compontents.  Should be called
     * before any function searches cached data by address name or inserts
     * values into the cache. All parameters are passed by reference and
     * normalized.
     *
     * @param	string		The street address.
     * @param	string		The city name.
     * @param	string		The state name.
     * @param	string		This ZIP code.
     * @param	string		The country name.
     * @return	none
     */
    public static function normalizeAddress(&$street, &$city, &$state, &$zip, &$country)
    {

        // pseudo normalize data: first letter uppercase.
        // @todo: get rid of this once we implement normalization properly
        $street = ucwords($street);
        $city 	= ucwords($city);
        $state 	= ucwords($state);

        // some zip codes contain letters, so just upper case them all
        $zip 	= strtoupper($zip);

        // if length of country string is 3 or less, it's probably an abbreviation;
        // make it all upper case then
        if (strlen($country) < 4) {
            $country = strtoupper($country);
        } else {
            $country= ucwords($country);
        }

        // to somehow normalize the data we get, we will check for country codes like DEU that the geocoder
        // doesn't understand and look up a real country name from static_info_countries
        // 1. check if static_info_tables is available
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables')) {

            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $countryRepo= $objectManager->get(\SJBR\StaticInfoTables\Domain\Repository\CountryRepository::class);

            // 2. check the length of the country and do lookup only if it's 2 or 3 characters
            $length = strlen($country);
            if ($length == 2) {

//                // try to find a country with that two character code
                $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('cn_short_en', 'static_countries', 'cn_iso_2=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($country, self::static_countries));
                $newCountry = $rows[0]['cn_short_en'];
                // $newCountry = $countryRepo->findAllowedByIsoCodeA2($country)->getFirst();

                if (!empty($newCountry)) {
                    $country = $newCountry;
                }
            } elseif ($length == 3) {
                // try to find a country with that two character code
                $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('cn_short_en', 'static_countries', 'cn_iso_3=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($country, self::static_countries));
                //$newCountry = $countryRepo->findAllowedByIsoCodeA3($country)->getFirst();
                $newCountry = $rows[0]['cn_short_en'];
                if (!empty($newCountry)) {
                    $country = $newCountry;
                }
            } elseif ($length > 0)  {
                // try to find a country with that two character code
                $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('cn_short_en', 'static_countries', 'cn_short_local=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($country, self::static_countries));
                // $newCountry = $countryRepo->findByOfficialNameLocal($country);
                $newCountry = $rows[0]['cn_short_en'];
                if (!empty($newCountry)) {
                    $country = $newCountry;
                }
            }
        }

        // if we still have no country, use the default one
        if (empty($country)) {
            $country = \JBartels\WecMap\Utility\Backend::getExtConf('defaultCountry');
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Using default country: ' . $country, 'wec_map_geocode');
            }
        }
    }

    /**
     * Returns the current geocoding status.  Geocoding may be successfull,
     * failed, or may not have been attempted.
     *
     * @param	string		The street address.
     * @param	string		The city name.
     * @param	string		The state name.
     * @param	string		This ZIP code.
     * @param	string		The country name.
     * @return	int		Status code. -1=Failed, 0=Not Completed, 1=Successfull.
     */
    public static function status($street, $city, $state, $zip, $country)
    {
        /* Look up the address in the cache table */
        $latlong = self::find($street, $city, $state, $zip, $country);

        /* Found a cached match */
        if ($latlong) {
            if ($latlong['lat']==0 and $latlong['long']==0) {
                $statusCode = -1; /* Previous lookup failed */
            } else {
                $statusCode = 1; /* Previous lookup succeeded */
            }
        } else {
            $statusCode = 0; /* Lookup has not been performed */
        }

        return $statusCode;
    }

    /**
     * Looks up the latitude and longitude of a specified address in the cache
     * table only.
     *
     * @param	string		The street address.
     * @param	string		The city name.
     * @param	string		The state name.
     * @param	string		This ZIP code.
     * @param	string		The country name.
     * @return	array		Lat/long array for specified address.  Null if lookup fails.
     */
    public static function find($street, $city, $state, $zip, $country)
    {
        $hash = self::hash($street, $city, $state, $zip, $country);
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_wecmap_cache', ' address_hash="' . $hash . '"');
        if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
            $latlong = ['lat' => $row['latitude'], 'long' => $row['longitude']];
            return $latlong;
        } else {
            return null;
        }
    }

    /**
     * Inserts an address with a specified latitude and longitdue into the cache table.
     *
     * @param	string		The street address.
     * @param	string		The city name.
     * @param	string		The state name.
     * @param	string		This ZIP code.
     * @param	string		The country name.
     * @param	string		Latidude.
     * @param	string		Longitude.
     * @return	none
     */
    public static function insert($street, $city, $state, $zip, $country, $lat, $long)
    {
        /* Check if value is already in DB */
        if (self::find($street, $city, $state, $zip, $country)) {
            /* Update existing entry */
            $latlong = ['latitude' => $lat, 'longitude' => $long];
            $result = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_wecmap_cache', "address_hash='" . self::hash($street, $city, $state, $zip, $country) . "'", $latlong);
        } else {
            /* Insert new entry */
            $insertArray = [];
            $insertArray['address_hash'] = self::hash($street, $city, $state, $zip, $country);
            $insertArray['address'] = $street . ' ' . $city . ' ' . $state . ' ' . $zip . ' ' . $country;
            $insertArray['latitude'] = $lat;
            $insertArray['longitude'] = $long;

            /* Write address to cache table */
            $result = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_wecmap_cache', $insertArray);
        }
    }

    /**
     * Update a cached entry based on its address hash.
     *
     * @param	string		Address hash.
     * @param	float		New latitude.
     * @param	float		New longitude.
     * @return	none
     */
    public static function updateByUID($uid, $lat, $long)
    {
        $latlong = ['latitude' => $lat, 'longitude' => $long];
        $result = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_wecmap_cache', 'address_hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($uid, 'tx_wecmap_cache'), $latlong);
    }

    /**
     * Deletes a cached entry based on its address hash.
     *
     * @return	none
     */
    public static function deleteByUID($uid)
    {
        $result = $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_wecmap_cache', 'address_hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($uid, 'tx_wecmap_cache'));
    }

    /**
     * Deletes all cached entries.
     *
     * @return	none
     */
    public static function deleteAll()
    {
        $result = $GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('tx_wecmap_cache');
    }

    /**
     * Deletes a specified address from the cache table.
     *
     * @param	string		The street address.
     * @param	string		The city name.
     * @param	string		The state name.
     * @param	string		This ZIP code.
     * @param	string		The country name.
     * @return	none
     */
    public static function delete($street, $city, $state, $zip, $country)
    {
        $result = $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_wecmap_cache', "address_hash='" . self::hash($street, $city, $state, $zip, $country) . "'");
    }

    /**
     * Creates the address hash, which acts as a unique identifier for the cache table.
     *
     * @param	string		The street address.
     * @param	string		The city name.
     * @param	string		The state name.
     * @param	string		This ZIP code.
     * @param	string		The country name.
     * @return	string		MD5 hash of the address.
     */
    public static function hash($street, $city, $state, $zip, $country)
    {
        $address_string = $street . ' ' . $city . ' ' . $state . ' ' . $zip . ' ' . $country;
        return md5($address_string);
    }

    /**
     *  Checks if the minimum amount of address data is available before
     *  geocoding.
     *
     * @param	string		The street address.
     * @param	string		The city name.
     * @param	string		The state name.
     * @param	string		This ZIP code.
     * @param	string		The country name.
     * @return	string		True if an address is empty. False otherwise.
     */
    public static function isEmptyAddress($street, $city, $state, $zip, $country)
    {
        if ($street == '' and $city == '' and $state == '' and $zip == '' and $country == '') {
            $isEmptyAddress = true;
        } else {
            $isEmptyAddress = false;
        }

        return $isEmptyAddress;
    }
}

//if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_cache.php'])	{
//	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_cache.php']);
//}
