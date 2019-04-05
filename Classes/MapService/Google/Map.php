<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JBartels\WecMap\MapService\Google;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Map implementation for the Google Maps mapping service.
 *
 */
class Map extends \JBartels\WecMap\MapService\Map
{
    public $lat;
    public $long;
    public $zoom;
    public $markers = [];
    public $width;
    public $height;
    public $mapName;

    public $js;
    public $control;
    public $type;
    public $directions;
    public $kml;
    public $prefillAddress;
    public $directionsDivID;
    public $showInfoOnLoad;
    public $maxAutoZoom = 15;
    public $static = false;
    public $key = null;
    // array to hold the different Icons
    public $icons;

    public $lang;
    public $LOCAL_LANG;
    /** @var $langService \TYPO3\CMS\Lang\LanguageService */
    public $langService;

    public $markerClassName = 'JBartels\\WecMap\\MapService\\Google\\Marker';
    /**
     *
     * @var \TYPO3\CMS\Core\Page\PageRenderer;
     */
    public $pageRenderer = null;
    /**
     * Class constructor.  Creates javscript array.
     * @access	public
     * @param	string		The Google Maps API Key
     * @param	string		The latitude for the center point on the map.
     * @param 	string		The longitude for the center point on the map.
     * @param	string		The initial zoom level of the map.
     */
    public function __construct($key, $width=250, $height=250, $lat='', $long='', $zoom='', $mapName='')
    {
        parent::__construct();
        $this->prefixId = 'tx_wecmap_map_google';
        $this->js = [];
        $this->markers = [];
        $this->kml = [];

        // array to hold the different Icons
        $this->icons = [];

        $this->directions = false;
        $this->directionsDivID = null;
        $this->prefillAddress = false;
        $this->showInfoOnLoad = false;
        $this->width = $width;
        $this->height = $height;

        if (($lat != '' && $lat != null) || ($long != '' && $long != null)) {
            $this->setCenter($lat, $long);
        }

        if ($zoom != '' && $zoom != null) {
            $this->setZoom($zoom);
        }

        if (empty($mapName)) {
            $mapName = 'map' . rand();
        }
        $this->mapName = $mapName;

        // Detect language
        if (TYPO3_MODE == 'BE') {
            $this->lang = $GLOBALS['BE_USER']->uc['lang'];
        } else {
            $this->lang = $GLOBALS['TSFE']->config['config']['language'];
        }
        if ($this->lang == 'default') {
            $this->lang = 'en';
        } elseif (empty($this->lang)) {
            $this->lang = 'en';
        }

        // load language file
        $this->langService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Lang\LanguageService::class);
        $this->langService->init($this->lang);
        $this->LOCAL_LANG = $this->langService->getParserFactory()->getParsedData('EXT:wec_map/Resources/Private/Languages/MapService/Google/locallang.xlf', $this->lang, '', 2);
        $this->pageRenderer =  \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
    }

    public function getLL($index)
    {
        return $this->langService->getLLL($index, $this->LOCAL_LANG);
    }

    /**
     * Enables controls for Google Maps, for example zoom level slider or mini
     * map. Valid controls are largeMap, smallMap, scale, smallZoom,
     * overviewMap, and mapType.
     *
     * @access	public
     * @param	string	The name of the control to add.
     * @return	none
     *
     **/
    public function addControl($name)
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': adding control ' . $name, 'wec_map_api');
        switch ($name) {
            case 'zoom':
            case 'largeMap':	// deprecated
            case 'smallMap':	// deprecated
            case 'smallZoom':	// deprecated
                $this->controls[] .= $this->js_addControl('new GZoomControl()');
                break;

            case 'scale':
                $this->controls[] .= $this->js_addControl('new GScaleControl()');
                break;

            case 'mapType':
                $this->controls[] .= $this->js_addMapType('G_PHYSICAL_MAP');
                $this->controls[] .= $this->js_addMapType('G_SATELLITE_MAP');
                $this->controls[] .= $this->js_addMapType('G_HYBRID_MAP');
                $this->controls[] .= $this->js_addMapType('G_OSM_MAP');
                $this->controls[] .= $this->js_addMapType('G_OCM_MAP');

                $this->controls[] .= $this->js_addControl('new GHierarchicalMapTypeControl()');
                break;

//			case 'googleEarth':
//				$this->controls[] .= 'WecMap.get("' . $this->mapName . '").addMapType(G_SATELLITE_3D_MAP);';
//				break;

            default:
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': ' . $name . '  not supported for addControl()', 'wec_map_api');
                break;
        }
    }

    /**
     * Sets the initial map type.  Valid defaults from Google are...
     *   G_NORMAL_MAP: This is the normal street map type.
     *   G_SATELLITE_MAP: This map type shows Google Earth satellite images.
     *   G_HYBRID_MAP: This map type shows transparent street maps over Google Earth satellite images.
     *	 G_PHYSICAL_MAP: displays physical map tiles based on terrain information.
     *   G_OSM_MAP: displays OpenStreetMap
     *   G_OCM_MAP: displays OpenCycleMap
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Main function to draw the map.  Outputs all the necessary HTML and
     * Javascript to draw the map in the frontend or backend.
     *
     * @access	public
     * @return	string	HTML and Javascript markup to draw the map.
     */
    public function drawMap()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': starting map drawing', 'wec_map_api', [
            'domain' => \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST'),
            'maptype' => $this->type
        ]);

        $hasThingsToDisplay = $this->hasThingsToDisplay();
        $hasHeightWidth = $this->hasHeightWidth();

        // make sure we have markers to display and an API key
        $domainmgr = \JBartels\WecMap\Utility\DomainMgr::getInstance();
        $browserKey = $domainmgr->getBrowserKey();

        if ($hasThingsToDisplay && $hasHeightWidth && $browserKey) {

            // auto center and zoom if necessary
            $this->autoCenterAndZoom();

            $htmlContent .= $this->mapDiv();

            $get = \TYPO3\CMS\Core\Utility\GeneralUtility::_GPmerged('tx_wecmap_api');

            // if we're forcing static display, skip the js
            if ($this->static && ($this->staticMode == 'force' || ($this->staticUrlParam && intval($get['static']) == 1))) {
                return $htmlContent;
            }

            $scheme = (\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://');
            // get the correct API URL
            $apiURL = $scheme . 'maps.googleapis.com/maps/api/js?language=' . $this->lang . '&libraries=places&callback=tp3_app.initialize';
            $apiURL = $domainmgr->addKeyToUrl($apiURL, $browserKey);

            $siteRelPath = PathUtility::stripPathSitePrefix(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wec_map'));

            if (true) {
                // todo fix \JBartels\WecMap\Utility\Backend::getExtConf('useOwnJS')
                $mmURL  = $siteRelPath . 'Resources/Public/JavaScript/ContribJS/markermanager.js';
                $ibURL  = $siteRelPath . 'Resources/Public/JavaScript/ContribJS/infobubble.js';
                $omURL  = $siteRelPath . 'Resources/Public/JavaScript/ContribJS/oms.min.js';
            } else {
                $extConf = $this->getExtConf();
                $mmURL  = $scheme . $extConf['mmURL'];
                $ibURL  = $scheme . $extConf['ibURL'];
                $omURL  = $scheme . $extConf['omURL'];
            }

            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': loading API from URL: ' . $apiURL, 'wec_map_api');

            /* If we're in the frontend, use TSFE.  Otherwise, include JS manually. */
            $jsDir = \JBartels\WecMap\Utility\Backend::getExtConf('jsDir');
            if (empty($jsDir)) {
                $jsDir = $siteRelPath . 'Resources/Public/JavaScript/';
            }
            $jsFile  = $jsDir . 'wecmap.js';
            $jsFile2 = $jsDir . 'copyrights.js';
            $jsFile3 = $jsDir . 'wecmap_backend.js';

            if (TYPO3_MODE == 'FE') {
                $this->pageRenderer->addJsFooterLibrary('wec_map_googleMaps_apiURL', $apiURL, 'text/javascript', false, false, '', true, '|', true, '');
                $this->pageRenderer->addJsFooterLibrary('wec_map_googleMaps_mmURL', $mmURL, 'text/javascript', true, false, '', false, '|', false, '');
                $this->pageRenderer->addJsFooterLibrary('wec_map_googleMaps_ibURL', $ibURL, 'text/javascript', true, false, '', false, '|', false, '');
                $this->pageRenderer->addJsFooterLibrary('wec_map_googleMaps_omURL', $omURL, 'text/javascript', true, false, '', false, '|', false, '');
                $this->pageRenderer->addJsFooterLibrary('wec_map_jsFile', $jsFile, 'text/javascript', true, false, '', false, '|', true, '');
                $this->pageRenderer->addJsFooterLibrary('wec_map_jsFile2', $jsFile2, 'text/javascript', true, false, '', false, '|', true, '');
            } else {
                $htmlContent .= '<script defer async="false" src="' . $apiURL . '" type="text/javascript"></script>';
                if (true) {
                    //\JBartels\WecMap\Utility\Backend::getExtConf('useOwnJS') ||
                    $htmlContent .= '<script defer async="false" src="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $mmURL . '" type="text/javascript"></script>';
                    $htmlContent .= '<script defer async="false" src="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $ibURL . '" type="text/javascript"></script>';
                    $htmlContent .= '<script defer async="false" src="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $omURL . '" type="text/javascript"></script>';
                } else {
                    $htmlContent .= '<script defer async="false" src="' . $mmURL . '" type="text/javascript"></script>';
                    $htmlContent .= '<script defer async="false" src="' . $ibURL . '" type="text/javascript"></script>';
                    $htmlContent .= '<script defer async="false" src="' . $omURL . '" type="text/javascript"></script>';
                }
                $htmlContent .= ($jsFile  ? '<script defer async="false" src="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $jsFile . '" type="text/javascript"></script>' : '')
                              . ($jsFile2 ? '<script defer async="false" src="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $jsFile2 . '" type="text/javascript"></script>' : '')
                              . ($jsFile3 ? '<script defer async="false" src="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $jsFile3 . '" type="text/javascript"></script>' : '')
                              ;
            }

            if ($this->enableOverlappingMarkerManager) {
                $mapOptions['enableOverlappingMarkerManager'] = true;
            }
            $jsContent = [];
            $jsContent[] = $this->js_createLabels();
            $jsContent[] = '';
            $jsContent[] = $this->js_drawMapStart($mapOptions);
            $jsContent[] = $this->js_newGDirections();
            $jsContent[] = $this->js_setCenter($this->lat, $this->long, $this->zoom, $this->type);
            if (is_array($this->controls)) {
                $jsContent = array_merge($jsContent, $this->controls);
            }
            $jsContent[] = $this->js_icons();
            if (is_array($this->groups)) {
                foreach ($this->groups as $key => $group) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': adding ' . $group->getMarkerCount() . ' markers from group ' . $group->id, 'wec_map_api');
                    $jsContent = array_merge($jsContent, $group->drawMarkerJS());
                    $jsContent[] = '';
                }
            }

            $jsContent[] = $this->js_initialOpenInfoWindow();
            $jsContent[] = $this->js_addKMLOverlay();
            $jsContent[] = $this->js_loadCalls();
            $jsContent[] = $this->js_drawMapEnd();

            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': finished map drawing', 'wec_map_api');

            // get our content out of the array into a string
            $jsContentString = implode(chr(10), $jsContent);

            // add js then return html
            $this->pageRenderer->addJsFooterInlineCode('wec_map_googleMaps_jsFile', $jsContentString);
            return $htmlContent;
        } elseif (!$hasThingsToDisplay) {
            $error = '<p>' . $this->getLL('error_nothingToDisplay') . '</p>';
        } elseif (!$hasHeightWidth) {
            $error = '<p>' . $this->getLL('error_noHeightWidth') . '</p>';
        } elseif (!$browserKey) {
            $error = '<p>' . $this->getLL('error_noBrowserKey') . '</p>';
        }
        \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': finished map drawing with errors', 'wec_map_api', 2);
        return $error;
    }

    /**
     * Draws the static map if desired
     *
     * @return String content
     **/
    public function drawStaticMap()
    {
        if (!$this->static) {
            return null;
        }

        $index = 0;
        if ($this->staticExtent == 'all') {
            $markerString = 'size:small';
            if ($this->staticLimit > 50) {
                $this->staticLimit = 50;
            }
            foreach ($this->groups as $key => $group) {
                foreach ($group->markers as $marker) {
                    if ($index >= $this->staticLimit) {
                        break 2;
                    }
                    $index++;
                    $markerString .= '|' . $marker->latitude . ',' . $marker->longitude;
                }
            }
            $img = $this->generateStaticMap($markerString);
            return $img;
        } elseif ($this->staticExtent == 'each') {
            foreach ($this->groups as $key => $group) {
                foreach ($group->markers as $marker) {
                    if ($index >= $this->staticLimit) {
                        break 2;
                    }
                    $markerString = 'size:small|' . $marker->latitude . ',' . $marker->longitude;
                    $img .= $this->generateStaticMap($markerString, false);
                    $index++;
                }
            }
            return $img;
        } else {
            return null;
        }
    }

    /**
     * undocumented function
     *
     * @return void
     **/
    public function generateStaticMap($markers, $center = true, $alt = '')
    {
        $scheme = (\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://');

        if ($center) {
            $url = $scheme . 'maps.googleapis.com/maps/api/staticmap?center=' . $this->lat . ',' . $this->long . '&zoom=' . $this->zoom . '&size=' . $this->width . 'x' . $this->height . '&maptype=' . $this->type . '&markers=' . urlencode($markers);
        } else {
            $url = $scheme . 'maps.googleapis.com/maps/api/staticmap?size=' . $this->width . 'x' . $this->height . '&maptype=' . $this->type . '&markers=' . urlencode($markers);
        }
        $domainmgr = \JBartels\WecMap\Utility\DomainMgr::getInstance();
        $url = $domainmgr->addKeyToUrl($url, $domainmgr->getBrowserKey(), $domainmgr->getStaticKey());
        return '<img class="tx-wecmap-api-staticmap" alt="' . $alt . '" src="' . $url . '" />';
    }
    /**
     * Adds an address to the currently list of markers rendered on the map. Support tabs.
     *
     * @param	string		The street address.
     * @param	string		The city name.
     * @param	string		The state or province.
     * @param	string		The ZIP code.
     * @param	string		The country name.
     * @param 	array 		Array of tab labels. Need to be kept short.
     * @param	array		Array of titles for the marker popup.
     * @param	array		Array of descriptions to be displayed in the marker popup.
     * @param	int		Minimum zoom level for marker to appear.
     * @param	int		Maximum zoom level for marker to appear.
     * @return	marker object
     * @todo	Zoom levels are very Google specific.  Is there a generic way to handle this?
     */
    public function addMarkerByAddressWithTabs($street, $city, $state, $zip, $country, $tabLabels = null, $title=null, $description=null, $minzoom = 0, $maxzoom = 18, $iconID = '')
    {
        /* Geocode the address */
        $latlong = \JBartels\WecMap\Utility\Cache::lookup($street, $city, $state, $zip, $country);

        /* Create a marker at the specified latitude and longitdue */
        return $this->addMarkerByLatLongWithTabs($latlong['lat'], $latlong['long'], $tabLabels, $title, $description, $minzoom, $maxzoom, $iconID);
    }

    /**
     * Adds an address string to the current list of markers rendered on the map.
     *
     * @param	string		The full address string.
     * @param	array 		Array of strings to be used as labels on the tabs
     * @param	array		The titles for the tabs of the marker popup.
     * @param	array		The descriptions to be displayed in the tabs of the marker popup.
     * @param	int		Minimum zoom level for marker to appear.
     * @param	int		Maximum zoom level for marker to appear.
     * @return	marker object
     * @todo	Zoom levels are very Google specific.  Is there a generic way to handle this?
     **/
    public function addMarkerByStringWithTabs($string, $tabLabels, $title=null, $description=null, $minzoom = 0, $maxzoom = 18, $iconID = '')
    {

        // first split the string into it's components. It doesn't need to be perfect, it's just
        // put together on the other end anyway
        $address = explode(',', $string);
        list($street, $city, $state, $country) = $address;

        /* Geocode the address */
        $latlong = \JBartels\WecMap\Utility\Cache::lookup($street, $city, $state, $zip, $country);

        /* Create a marker at the specified latitude and longitdue */
        return $this->addMarkerByLatLongWithTabs($latlong['lat'], $latlong['long'], $tabLabels, $title, $description, $minzoom, $maxzoom, $iconID);
    }

    /**
     * Adds a marker from TCA info with tabs
     *
     * @param	string		The table name
     * @param 	int		The uid of the record to be mapped
     * @param	array 		Array of strings to be used as labels on the tabs
     * @param	array		The titles for the tabs of the marker popup.
     * @param	array		The descriptions to be displayed in the tabs of the marker popup.
     * @param	int		Minimum zoom level for marker to appear.
     * @param	int		Maximum zoom level for marker to appear.
     * @return	marker object
     **/
    public function addMarkerByTCAWithTabs($table, $uid, $tabLabels, $title=null, $description=null, $minzoom = 0, $maxzoom = 18, $iconID = '')
    {
        $uid = (int)$uid;

        // first get the mappable info from the TCA
        $tca = $GLOBALS['TCA'][$table]['ctrl']['EXT']['wec_map'];

        if (!$tca) {
            return false;
        }
        if (!$tca['isMappable']) {
            return false;
        }

        $streetfield  = \JBartels\WecMap\Utility\Shared::getAddressField($table, 'street');
        $cityfield    = \JBartels\WecMap\Utility\Shared::getAddressField($table, 'city');
        $statefield   = \JBartels\WecMap\Utility\Shared::getAddressField($table, 'state');
        $zipfield     = \JBartels\WecMap\Utility\Shared::getAddressField($table, 'zip');
        $countryfield = \JBartels\WecMap\Utility\Shared::getAddressField($table, 'country');

        // get address from db for this record
        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $statement = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
            )
            ->execute();
        $record = $statement->fetch();

        $street = $record[$streetfield];
        $city 	= $record[$cityfield];
        $state 	= $record[$statefield];
        $zip	= $record[$zipfield];
        $country= $record[$countryfield];

        if (empty($country) && $countryfield == 'static_info_country') {
            $country = $record['country'];
        } elseif (empty($country) && $countryfield == 'country') {
            $country = $record['static_info_country'];
        }

        /* Geocode the address */
        $latlong = \JBartels\WecMap\Utility\Cache::lookup($street, $city, $state, $zip, $country);

        /* Create a marker at the specified latitude and longitdue */
        return $this->addMarkerByLatLongWithTabs($latlong['lat'], $latlong['long'], $tabLabels, $title, $description, $minzoom, $maxzoom, $iconID);
    }

    /**
     * Adds a lat/long to the currently list of markers rendered on the map.
     *
     * @param	float		The latitude.
     * @param	float		The longitude.
     * @param	string		The title for the marker popup.
     * @param	string		The description to be displayed in the marker popup.
     * @param	int		Minimum zoom level for marker to appear.
     * @param	int		Maximum zoom level for marker to appear.
     * @return	marker object
     * @todo	Zoom levels are very Google specific.  Is there a generic way to handle this?
     */
    public function addMarkerByLatLongWithTabs($lat, $long, $tabLabels = null, $title=null, $description=null, $minzoom = 0, $maxzoom = 18, $iconID = '')
    {
        if (!empty($this->radius)) {
            $distance = $this->getDistance($this->lat, $this->long, $lat, $long);

            if (!empty($this->lat) && !empty($this->long) &&  $distance > $this->radius) {
                return null;
            }
        }

        if ($lat != '' && $long != '') {
            $group = $this->addGroup($minzoom, $maxzoom);
            $marker = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                $this->getMarkerClassName(),
                $group->getMarkerCount(),
                $lat,
                $long,
                $title,
                $description,
                $this->prefillAddress,
                $tabLabels,
                '0xFF0000',
                '0xFFFFFF',
                $iconID
            );
            $marker->setMinZoom($minzoom);
            $marker->setMapName($this->mapName);
            $group->addMarker($marker);
            $group->setDirections($this->directions);

            return $marker;
        }
        return null;
    }

    /**
     * Adds more custom icons to the Javascript Code
     * Takes an assoc. array with the following keys:
     * $iconID, $imagepath, $shadowpath, $width, $height,
     * $shadowWidth, $shadowHeight, $anchorX, $anchorY,
     * $infoAnchorX, $infoAnchorY
     *
     * @return 		boolean
     * @access   	public
     */
    public function addMarkerIcon($dataArray, &$cObj=null)
    {
        if (empty($dataArray)) {
            return false;
        } else {
            if ($cObj && is_array($dataArray)) {
                $sData = $dataArray;
                $dataArray = [];
                foreach ($sData as $theKey => $theValue) {
                    if (substr($theKey, -1, 1) == '.') {
                        $dataArray[ substr($theKey, 0, -1) ] = $cObj->stdWrap($sData[substr($theKey, 0, -1)], $sData[$theKey]);
                    } else {
                        $dataArray[$theKey] = $sData[$theKey];
                    }
                }
            }

            $this->icons[] = 'WecMap.addIcon("' . $this->mapName . '", "' . $dataArray['iconID'] . '", "' . $dataArray['imagepath'] . '", "' . $dataArray['shadowpath'] . '", new google.maps.Size(' . $dataArray['width'] . ', ' . $dataArray['height'] . '), new google.maps.Size(' . $dataArray['shadowWidth'] . ', ' . $dataArray['shadowHeight'] . '), new google.maps.Point(' . $dataArray['anchorX'] . ', ' . $dataArray['anchorY'] . '), new google.maps.Point(' . $dataArray['infoAnchorX'] . ', ' . $dataArray['infoAnchorY'] . '));';
            return true;
        }
    }

    /**
     * Adds a KML overlay to the map.
     *
     * @return void
     **/
    public function addKML($url)
    {
        $this->kml[] = $url;
    }

    /**
     * Sets the map center to a given address' coordinates.
     *
     * @return void
     **/
    public function setCenterByAddress($street, $city, $state, $zip, $country = null)
    {

        /* Geocode the address */
        $latlong = \JBartels\WecMap\Utility\Cache::lookup($street, $city, $state, $zip, $country);
        $this->lat = $latlong['lat'];
        $this->long = $latlong['long'];
    }

    /**
     * Creates the overall map div.
     *
     * @access	private
     * @return	string		The HTML for the map div tag.
     */
    public function mapDiv()
    {
        $staticContent = $this->drawStaticMap();
        if ($this->static) {
            $height = '100%';
        } else {
            $height = $this->height . 'px';
        }
        return '<div id="' . $this->mapName . '" class="tx-wecmap-map" style="width:' . $this->width . 'px; height:' . $height . ';">' . $staticContent . '</div>';
    }

    /**
     * Adds some language specific markers to the global WecMap JS object.
     *
     * @access	private
     * @return	string		The Javascript code for the labels.
     */
    public function js_createLabels()
    {
        $content = '
function InitWecMapGoogleV3Labels() {
	WecMap.labels.startaddress = ' . json_encode($this->getLL('startaddress')) . ';
	WecMap.labels.endaddress = ' . json_encode($this->getLL('endaddress')) . ';
	WecMap.labels.OSM = ' . json_encode($this->getLL('OSM')) . ';
	WecMap.labels.OSM_alt = ' . json_encode($this->getLL('OSM-alt')) . ';
	WecMap.labels.OSM_bike = ' . json_encode($this->getLL('OSM-bike')) . ';
	WecMap.labels.OSM_bike_alt = ' . json_encode($this->getLL('OSM-bike-alt')) . ';
	WecMap.labels.locale =  ' . json_encode($this->lang) . ';
	/* error messages */
	WecMap.labels.INVALID_REQUEST = ' . json_encode($this->getLL('INVALID_REQUEST')) . ';
	WecMap.labels.MAX_WAYPOINTS_EXCEEDED = ' . json_encode($this->getLL('MAX_WAYPOINTS_EXCEEDED')) . ';
	WecMap.labels.NOT_FOUND = ' . json_encode($this->getLL('NOT_FOUND')) . ';
	WecMap.labels.OK = ' . json_encode($this->getLL('OK')) . ';
	WecMap.labels.OVER_QUERY_LIMIT = ' . json_encode($this->getLL('OVER_QUERY_LIMIT')) . ';
	WecMap.labels.REQUEST_DENIED = ' . json_encode($this->getLL('REQUEST_DENIED')) . ';
	WecMap.labels.UNKNOWN_ERROR = ' . json_encode($this->getLL('UNKNOWN_ERROR')) . ';
	WecMap.labels.ZERO_RESULTS = ' . json_encode($this->getLL('ZERO_RESULTS')) . ';

	WecMap.osmMapType.name = WecMap.labels.OSM;
	WecMap.osmMapType.alt = WecMap.labels.OSM_alt;
	WecMap.osmCycleMapType.name = WecMap.labels.OSM_bike;
	WecMap.osmCycleMapType.alt = WecMap.labels.OSM_bike_alt;
}';
        return $content;
    }

    /**
     * Creates the beginning of the drawMap function in Javascript.
     *
     * @access	private
     * @return	string	The beginning of the drawMap function in Javascript.
     */
    public function js_drawMapStart()
    {
        $js =  'var tp3_app = tp3_app || {};
        var WECInit =  function(){
	
if ( !window["WecMap"] )
	WecMap = createWecMap();
WecMap.init();
InitWecMapGoogleV3Labels();
WecMap.createMap("' . $this->mapName . '" );';

        if ($this->mapOptions['enableOverlappingMarkerManager']) {
            $js .= 'WecMap.enableOverlappingMarkerManager("' . $this->mapName . '", true );';
        }
        return $js;
    }

    /**
     * Creates the end of the drawMap function in Javascript.
     *
     * @access	private
     * @return	string	The end of the drawMap function in Javascript.
     */
    public function js_drawMapEnd()
    {
        return '	WecMap.drawMap( "' . $this->mapName . '" );	};tp3_app.initialize= tp3_app.initialize || WECInit();';
    }

    /**
     * Creates the Google Directions Javascript object.
     *
     * @access	private
     * @param	string		Name of the map object that the direction overlay will be shown on.
     * @return	string		Javascript for the Google Directions object.
     */
    public function js_newGDirections()
    {
        if ($this->directionsDivID == null) {
            return '    WecMap.createDirections( "' . $this->mapName . '" );';
        } else {
            return '    WecMap.createDirections( "' . $this->mapName . '", "' . $this->directionsDivID . '" );';
        }
    }

    public function js_setMapType($type)
    {
        return 'WecMap.setMapType("' . $this->mapName . '", ' . $type . ');';
    }

    public function js_addMapType($type)
    {
        return 'WecMap.addMapType("' . $this->mapName . '", ' . $type . ');';
    }

    /**
     * Creates the map's center point in Javascript.
     *
     * @access	private
     * @param	float		Center latitude.
     * @param	float		Center longitude.
     * @param	int		Initial zoom level.
     * @return	string		Javascript to center and zoom the specified map.
     */
    public function js_setCenter($lat, $long, $zoom, $type)
    {
        if ($type) {
            return 'WecMap.setCenter("' . $this->mapName . '", new google.maps.LatLng(' . $lat . ', ' . $long . '), ' . $zoom . ', ' . $type . ');';
        } else {
            return 'WecMap.setCenter("' . $this->mapName . '", new google.maps.LatLng(' . $lat . ', ' . $long . '), ' . $zoom . ');';
        }
    }

    /**
     * Creates Javascript to add map controls.
     *
     * @access	private
     * @param	string		Javascript to add a control to the map.
     */
    public function js_addControl($control)
    {
        return 'WecMap.addControl("' . $this->mapName . '", ' . $control . ');';
    }

    /**
     * generate the js for kml overlays
     *
     * @return string
     **/
    public function js_addKMLOverlay()
    {
        $out = [];
        foreach ($this->kml as $url) {
            $out[] = 'WecMap.addKML("' . $this->mapName . '", "' . $url . '");';
        }
        return implode("\n", $out);
    }

    /**
     * Creates Javascript to define marker icons.
     *
     * @access	private
     * @return	string		Javascript definitions for marker icons.
     */
    public function js_icons()
    {
        /* If we're in the backend, get an absolute path.  Frontend can use a relative path. */
        $siteRelPath = PathUtility::stripPathSitePrefix(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wec_map'));
        if (TYPO3_MODE=='BE') {
            $path = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $siteRelPath;
        } else {
            $path = $siteRelPath;
        }
        // add default-icon
        $this->addMarkerIcon([
            'iconID'        => 'default',
            'imagepath'     => $path . 'Resources/Public/Images/mm_20_red.png',
            'shadowpath'    => $path . 'Resources/Public/Images/mm_20_shadow.png',
            'width'         => 12,
            'height'        => 20,
            'shadowWidth'   => 22,
            'shadowHeight'  => 20,
            'anchorX'       => 6,
            'anchorY'       => 20,
            'infoAnchorX'   => 5,
            'infoAnchorY'   => 1,
        ]);
        return implode("\n", $this->icons);
    }

    /**
     * Write the javascript to open the info window if there is only one marker
     *
     * @return string 	javascript
     **/
    public function js_initialOpenInfoWindow()
    {
        $markers = reset($this->markers);
        if (is_array($markers) && count($markers) == 1 && $this->showInfoOnLoad) {
            foreach ($this->groups as $key => $group) {
                foreach ($group->markers as $marker) {
                    return $marker->getInitialOpenInfoWindowJS();  // return 1st marker
                }
            }
        }
        return '';
    }

    /**
     * Returns the Javascript that is responsible for loading and unloading
     * the maps.
     *
     * @return string The javascript output
     **/
    public function js_loadCalls()
    {
        $loadCalls  = 'if(document.getElementById("' . $this->mapName . '_radiusform") != null) document.getElementById("' . $this->mapName . '_radiusform").style.display = "";';
        $loadCalls .= 'if(document.getElementById("' . $this->mapName . '_sidebar") != null) document.getElementById("' . $this->mapName . '_sidebar").style.display = "";';
        $loadCalls .= 'document.getElementById("' . $this->mapName . '").style.height="' . $this->height . 'px";';
        return $loadCalls;
    }

    /**
     * Sets the center and zoom values for the current map dynamically, based
     * on the markers to be displayed on the map.
     *
     * @access	private
     * @return	none
     */
    public function autoCenterAndZoom()
    {

        /* Get center and lat/long spans from parent object */
        $latLongData = $this->getLatLongData();

        $lat = $latLongData['lat']; /* Center latitude */
        $long = $latLongData['long']; /* Center longitude */
        $latSpan = $latLongData['latSpan']; /* Total latitude the map covers */
        $longSpan = $latLongData['longSpan']; /* Total longitude the map covers */

        // process center
        if (!isset($this->lat) or !isset($this->long)) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': setting center to ' . $lat . ', ' . $long, 'wec_map_api');
            $this->setCenter($lat, $long);
        }

        // process zoom
        if (!isset($this->zoom) || $this->zoom == '') {
            $this->setZoom($this->getAutoZoom($latSpan, $longSpan));
        }

        // prepare parameters for the center and zoom hook
        $hookParameters = ['lat' => &$this->lat, 'long' => &$this->long, 'zoom' => &$this->zoom];

        // process centerAndZoom hook; allows to manipulate zoom and center before displaying the map
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecmap_api']['centerAndZoomHook'])) {
            $hooks =& $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecmap_api']['centerAndZoomHook'];
            $hookReference = null;
            foreach ($hooks as $hookFunction) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hookFunction, $hookParameters, $hookReference);
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': Called hook. Potentially new lat/long/zoom', 'wec_map_api', 2, [
                    Lat => $this->lat,
                    Long => $this->long,
                    Zoom => $this->zoom
                ]);
            }
        }
    }

    /**
     * Calculates the auto zoom
     *
     * @return int 	zoom level
     **/
    public function getAutoZoom($latSpan, $longSpan)
    {
        if ($longSpan <= 0 || $latSpan <= 0) {
            return $this->maxAutoZoom;
        }

        $wZoom = log($this->width, 2) - log($longSpan, 2);
        $hZoom = log($this->height, 2) - log($latSpan, 2);

        /* Pick the lower of the zoom levels since we'd rather show too much */
        $zoom = floor(($wZoom < $hZoom) ? $wZoom : $hZoom);

        /* Don't zoom in too far if we only have a single marker.*/
        if ($zoom > $this->maxAutoZoom) {
            $zoom = $this->maxAutoZoom;
        }
        \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': set zoom ' . $zoom, 'wec_map_api');
        return $zoom;
    }

    /**
     * Checks if a map has markers or a
     * specific center.Otherwise, we have nothing
     * to draw.
     * @return        bool        True/false whether the map is valid or not.
     */
    public function hasThingsToDisplay()
    {
        $valid = false;

        if (count($this->groups) > 0) {
            $validMarkers = false;
            foreach ($this->groups as $key => $group) {
                if ($group->hasMarkers()) {
                    $validMarkers = true;
                }
            }
        } else {
            $validMarkers = false;
        }

        if (isset($this->lat) and isset($this->long)) {
            $validCenter = true;
        }

        // If we have an API key along with markers or a center point, it's valid
        if ($validMarkers or $validCenter) {
            $valid = true;
        }

        if (count($this->kml)) {
            $valid = true;
        }

        return $valid;
    }

    /**
     * Checks whether the map has a height and width set.
     *
     * @return boolean
     **/
    public function hasHeightWidth()
    {
        if (!empty($this->width) && !empty($this->height)) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': height: ' . $this->height . ', width: ' . $this->width, 'wec_map_api');
            return true;
        } else {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': width or height missing', 'wec_map_api', 3);
            return false;
        }
    }

    /**
     * Enables directions
     *
     * @param bool	Whether or not to prefill the currently logged in FE user's address already
     * @param string	The id of the container that will show the written directions
     *
     * @return void
     **/
    public function enableDirections($prefillAddress = false, $divID = null)
    {
        $this->prefillAddress = $prefillAddress;
        if ($prefillAddress && $divID) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': enabling directions with prefill and written dirs', 'wec_map_api');
        } elseif ($prefillAddress && !$divID) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': enabling directions with prefill and without written dirs', 'wec_map_api');
        } elseif (!$prefillAddress && $divID) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': enabling directions without prefill but with written dirs', 'wec_map_api');
        } elseif (!$prefillAddress && !$divID) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': enabling directions without prefill and written dirs', 'wec_map_api');
        }
        $this->directions = true;
        $this->directionsDivID = $divID;
    }

    /**
     * Enables static maps
     *
     * @param $mode String either automatic or force
     * @param $extent String either all or each
     * @param $urlParam boolean enable URL parameter to force static map
     * @param $limit int Limit of markers on a map or marker maps
     *
     * @return void
     **/
    public function enableStatic($mode='automatic', $extent='all', $urlParam=false, $limit=50)
    {
        $this->static = true;
        if (empty($mode)) {
            $mode = 'automatic';
        }
        $this->staticMode = $mode;
        if (empty($extent)) {
            $extent = 'all';
        }
        $this->staticExtent = $extent;
        if (empty($urlParam)) {
            $urlParam = false;
        }
        $this->staticUrlParam = $urlParam;
        if (empty($limit)) {
            $limit = 50;
        }
        $this->staticLimit = $limit;

        \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': Enabling static maps: ' . $mode . ':' . $extent . ':' . $urlParam . ':' . $limit, 'wec_map_api');
    }

    /**
     * Makes the marker info bubble show on load if there is only one marker on the map
     *
     * @return void
     **/
    public function showInfoOnLoad()
    {
        $this->showInfoOnLoad = true;

        \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': Showing info bubble on load', 'wec_map_api');
    }

    /**
     * Sets the maximum zoom level that autozoom will use
     *
     * @return void
     **/
    public function setMaxAutoZoom($newZoom = null)
    {
        if ($newZoom != null) {
            $this->maxAutoZoom = intval($newZoom);
        }
    }

    /**
     * @return array extConf
     */
    protected function getExtConf()
    {
        if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) >= 9000000) {
            return GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('wec_map');
        } else {
            return unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wec_map']);
        }
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/map_service/google/class.tx_wecmap_map_google.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/map_service/google/class.tx_wecmap_map_google.php']);
}
