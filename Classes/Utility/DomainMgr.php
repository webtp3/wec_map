<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JBartels\WecMap\Utility;

/**
 * Domain <=> API Key manager class for the WEC Map extension.  This class
 * provides user functions for handling domains and API keys
 *
 */
class DomainMgr
{
    /**
     *
     */
    public $cObj = null;

    public $extKey = 'wec_map';
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    public function addKeyToUrl($url, $key, $addSecret)
    {
        $apiKey = explode(',', $key);
        $url .= '&key=' . $apiKey[ 0 ];
        if ($apiKey[ 1 ] && $addSecret) {
            return $this->signurl($url, $apiKey[ 1 ]);
        } else {
            return $url;
        }
    }

    public function getBrowserKey($domain = null)
    {
        $value = $this->getKey($domain);
        $values = explode('&', $value);
        if ($values[ 0 ] == '') {
            $values[ 0 ] = 'AIzaSyAeFL1mw0cUjDZ5kSM7nTQiXgLTDZGJUwg';
        }
        return $values[ 0 ];
    }

    public function getServerKey($domain = null)
    {
        $value = $this->getKey($domain);
        $values = explode('&', $value);
        return $values[ 1 ];
    }

    protected function getKey($domain = null)
    {

        // check to see if this is an update from the old config schema and convert to the new
        $isOld = $this->checkForOldConfig();

        // get key from configuration
        $keyConfig = \JBartels\WecMap\Utility\Backend::getExtConf('apiKey.google');

        // if we are using the old config, return the old key this time. It will be changed for next time.
        if ($isOld) {
            return $keyConfig;
        }

        // get current domain
        if ($domain == null) {
            $domain = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST');
        }

        // loop through all the domain->key pairs we have to find the right one
        $found = false;
        foreach ($keyConfig as $key => $value) {
            if ($domain == $key) {
                $found = true;
                return $value;
            }
        }

        // if we didn't get an exact match, check for partials and guess
        if (!$found) {
            foreach ($keyConfig as $key => $value) {
                if (strpos($domain, $key) !== false) {
                    $found = true;
                    return $value;
                }
            }
        } else {
            return null;
        }
    }

    public function checkForOldConfig()
    {
        global $TYPO3_CONF_VARS;

        $keyConfig = \JBartels\WecMap\Utility\Backend::getExtConf('apiKey.google');
        if (is_array($keyConfig)) {
            return false;
        }

        $key = $keyConfig;
        $domain = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST');
        $this->saveApiKey([$domain => $key]);

        return true;
    }

    public function processPost($post)
    {
        $allDomains = $this->getAllDomains();

        // prepare the two arrays we need in the loop
        $extconfArray = [];
        $returnArray = [];

        // loop through all the pairs
        for ($i=0; array_key_exists('domain_' . $i, $post); $i++) {

            // get the domain and key
            $curDomain = $post[ 'domain_' . $i];
            $browserKey = $post[ 'browserkey_' . $i];
            $serverKey = $post[ 'serverkey_' . $i];

            // if there is no key, we don't want to save it in extconf
            if (!(empty($browserKey) && empty($serverKey)) && !empty($curDomain)) {
                $extconfArray[$curDomain] = $browserKey . '&' . $serverKey;
            }

            // get all but manually added domains
            $domains1 = $this->getRequestDomain();
            $domains2 = $this->getDomainRecords();
            $domains = array_keys(array_merge($domains1, $domains2));

            // if there is no domain, or we want to delete a domain, we won't return it.
            // we also make sure not to recommend domains that were just deleted but manually added before
            if (!empty($curDomain) && !(!empty($allDomains[$curDomain]) && empty($browserKey) && empty($serverKey) && !in_array($curDomain, $domains))) {
                $returnArray[$curDomain] = $browserKey . '&' . $serverKey;
            }
        }

        // save the domain->key pairs, even if empty
        $this->saveApiKey($extconfArray);

        // sort the array and reverse it so we show filled out records first, empty ones last
        asort($returnArray);

        return array_reverse($returnArray);
    }

    /*
     * Looks up the API key in extConf within localconf.php
     * @return		array		The Google Maps API keys.
     */
    public function getApiKeys()
    {
        $apiKeys = \JBartels\WecMap\Utility\Backend::getExtConf('apiKey.google');

        return $apiKeys;
    }

    /*
     * Saves the API key to extConf in localconf.php.
     * @param		string		The new Google Maps API Key.
     * @return		none
     */
    public function saveApiKey($dataArray)
    {
        global $TYPO3_CONF_VARS;
        if (class_exists('TYPO3\CMS\Core\Configuration\ExtensionConfiguration')) {
            $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
            );
            $extConf = $extensionConfiguration->get('wec_map');
        } else {
            // Fallback for CMS8
            // @extensionScannerIgnoreLine
            $extConf = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wec_map'];
            if (!is_array($extConf)) {
                $extConf = unserialize($extConf);
            }
        }
        if (!is_array($extConf)) {
            $extConf = unserialize($extConf);
        }
        //$extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf'][$this->extKey]);
        $extConf['apiKey.']['google'] = $dataArray;

        // Instance of install tool
        //$instObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_install');
        //$instObj->allowUpdateLocalConf = 1;
        //$instObj->updateIdentity = $this->extKey;

        // Get lines from localconf file
        //$lines = $instObj->writeToLocalconf_control();

        //$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXT\'][\'extConf\'][\''.$this->extKey.'\']', serialize($extConf));
        //$instObj->writeToLocalconf_control($lines);

        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $instObj = $this->objectManager->get(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);
        // Get lines from localconf file
        $lines = $instObj->getLocalConfigurationFileLocation();
        //$instObj->removeLocalConfigurationKeysByPath( array( 'EXT/extConf/'.$this->extKey ) ) ;
        $instObj->setLocalConfigurationValueByPath('EXT/extConf/' . $this->extKey, serialize($extConf));
    }

    /**
     * Returns an assoc array with domains as key and api key as value
     *
     * @return array
     **/
    public function getAllDomains()
    {
        $domainRecords = $this->getDomainRecords();

        // get domains entries from extconf
        $extconfDomains = $this->getApiKeys();

        // get domain from the current http request
        $requestDomain = $this->getRequestDomain();

        // Now combine all the records we got into one array with the domain as key and the api key as value
        return $this->combineAndSort($domainRecords, $extconfDomains, $requestDomain);
    }

    /**
     * Returns an assoc array with domain record domains as keys and api key as value
     *
     * @return array
     **/
    public function getDomainRecords()
    {

        // get domain records
        $domainRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('domainName', 'sys_domain', 'hidden=0');

        $newArray = [];
        foreach ($domainRecords as $key => $value) {
            $newArray[$value['domainName']] = '';
        }

        return $newArray;
    }

    /**
     * Returns the domain of the current http request
     *
     * @return array
     **/
    public function getRequestDomain()
    {
        // get domain from the current http request
        $requestDomain = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST');

        return [$requestDomain => ''];
    }

    /**
     * combine all the arrays, making each key unique and preferring the one that has a value,
     * then sort so that all empty values are last
     *
     * @return array
     **/
    public function combineAndSort($a1, $a2, $a3)
    {
        if (!is_array($a1)) {
            $a1 = [];
        }
        if (!is_array($a2)) {
            $a2 = [];
        }
        if (!is_array($a3)) {
            $a3 = [];
        }

        // combine the first and the second
        $temp1 = [];
        foreach ($a1 as $key => $value) {
            // if there is the same key in array2, check the values
            if (array_key_exists($key, $a2)) {

                // if a2 doesn't have a value, use a1's value
                if (empty($a2[$key])) {
                    $temp1[$key] = $value;
                } else {
                    $temp1[$key] = $a2[$key];
                }
            } else {
                $temp1[$key] = $value;
            }
        }

        $temp2 = array_merge($a2, $temp1);

        // combine the temp and the third
        $temp3 = [];
        foreach ($temp2 as $key => $value) {
            // if there is the same key in array2, check the values
            if (array_key_exists($key, $a3)) {

                // if a3 doesn't have a value, use a1's value
                if (empty($a3[$key])) {
                    $temp3[$key] = $value;
                } else {
                    $temp3[$key] = $a3[$key];
                }
            } else {
                $temp3[$key] = $value;
            }
        }

        // merge the third into the second
        $temp4 = array_merge($a3, $temp3);

        // sort by value, reverse, and return
        asort($temp4);

        return array_reverse($temp4);
    }

    public function signurl($url, $privatekey)
    {
        // sign only path and query without host and protocol
        $url = parse_url($url);
        $urlToSign =  $url[ 'path' ] . '?' . $url[ 'query' ];

        // Decode the private key
        $decodedKey = base64_decode(str_replace([ '-', '_' ], [ '+', '/' ], $privatekey));

        // Create a HMAC SHA1 signature and encode it
        $signature = hash_hmac('sha1', $urlToSign, $decodedKey, true);
        $encodedSignature = str_replace([ '+', '/' ], [ '-', '_' ], base64_encode($signature));

        return $url[ 'scheme' ] . '://' . $url[ 'host' ] . $url[ 'path' ] . '?' . $url[ 'query' ] . '&signature=' . urlencode($encodedSignature);
    }
}

//if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_domainmgr.php'])	{
//	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_domainmgr.php']);
//}
