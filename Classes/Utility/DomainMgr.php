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
    protected $extKey = 'wec_map';

    /**
     * #  TYPO3\CMS\Extbase\Annotation\Inject
     * #inject
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
    * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager;
    */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function addKeyToUrl($url, $key, $secret = null)
    {
        $url .= '&key=' . $key;
        if ($Secret) {
            return $this->signurl($url, $secret);
        } else {
            return $url;
        }
    }
    public function getContantBrowserKey()
    {
        // #todo geht key from contants
        return 'AIzaSyAeFL1mw0cUjDZ5kSM7nTQiXgLTDZGJUwg';
    }
    public function getContantServerKey()
    {
        return 'AIzaSyDXfFWzaRDLEzkkJn7dxY20WLaLKwy6TXs';
    }
    /**
     * Returns the browserKey
     *
     * @param string $domain
     *
     * @return string $browserKey
     */
    public function getBrowserKey($domain = null)
    {
        $domainRecord = $this->getDomainRecord($domain);
        if ($domainRecord == null) {
            return '';
        }

        return $domainRecord->getBrowserKey() != '' ? $domainRecord->getBrowserKey() : $this->getContantBrowserKey();
    }

    public function getServerKey($domain = null)
    {
        $domainRecord = $this->getDomainRecord($domain);
        if ($domainRecord == null) {
            return '';
        }
        return $domainRecord->getServerKey() != '' ? $domainRecord->getServerKey() : $this->getContantServerKey();
    }

    public function getStaticKey($domain = null)
    {
        $domainRecord = $this->getDomainRecord($domain);
        if ($domainRecord == null) {
            return '';
        }
        return $domainRecord->getStaticKey();
    }

    protected function getDomainRecord($domain = null)
    {
        // get current domain
        if ($domain == null) {
            $domain = $this->getRequestDomain();
        }

        // test all levels of domain
        for ($domainParts = explode('.', $domain); $domainParts; array_shift($domainParts)) {
            $domainTest = implode('.', $domainParts);
            $record = $this->getSingleDomain($domainTest);
            if ($record != null) {
                return $record;
            }
        }
        return null;
    }

    /*
     * obsolete! Keep code as example for updater
     *
     * Saves the API key to extConf in localconf.php.
     * @param		string		The new Google Maps API Key.
     * @return		none
     */
    private function saveApiKey($dataArray)
    {
        global $TYPO3_CONF_VARS;

        $extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf'][$this->extKey]);
        $extConf['apiKey.']['google'] = $dataArray;

        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $instObj = $this->objectManager->get(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);
        // Get lines from localconf file
        $lines = $instObj->getLocalConfigurationFileLocation();
        $instObj->removeLocalConfigurationKeysByPath([ 'EXT/extConf/' . $this->extKey ]);
        $instObj->setLocalConfigurationValueByPath('EXT/extConf/' . $this->extKey, serialize($extConf));
    }

    /**
     * Returns domain record for domain
     *
     * @param string domain
     * @return JBartels\\WecMap\\Domain\\Model\\DomainModel
     **/
    protected function getSingleDomain($domain)
    {
        // get domain record
        $queryResult = $this->getRepository()->findByDomain($domain);
        $domainRecord = $queryResult->current();
        if ($domainRecord != false) {
            return $domainRecord;
        }
        return null;
    }

    /**
     * Returns domain record for domain
     *
     * @return JBartels\\WecMap\\Domain\\Repository\\DomainRepository
     **/
    protected function getRepository()
    {
        // get domain records
        return $this->objectManager->get(\JBartels\WecMap\Domain\Repository\DomainRepository::class);
    }

    /**
     * Returns the domain of the current http request
     *
     * @return string
     **/
    protected function getRequestDomain()
    {
        // get domain from the current http request
        $requestDomain = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST');

        return $requestDomain;
    }

    protected function signurl($url, $privatekey)
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

    public static function getInstance()
    {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        return $objectManager->get(self::class);
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_domainmgr.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_domainmgr.php']);
}
