<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JBartels\WecMap\Plugin\FEUserMap;

/**
 * Frontend User Map plugin for displaying all frontend users on a map.
 */
class FEUserMap extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    public $prefixId = 'tx_wecmap_pi2';		// Same as class name
    public $scriptRelPath = 'Classes/Plugin/FEUserMap/FEUserMap.php';	// Path to this script relative to the extension dir.
    public $extKey = 'wec_map';	// The extension key.
    public $pi_checkCHash = true;
    public $sidebarLinks = [];

    /**
     * Draws a Google map containing all frontend users of a website.
     *
     * @param	array		The content array.
     * @param	array		The conf array.
     * @return	string	HTML / Javascript representation of a Google map.
     */
    public function main($content, $conf)
    {
        $this->conf=$conf;
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL('EXT:wec_map/Resources/Private/Languages/Plugin/FEUserMap/locallang.xlf');

        // check for WEC Map API static template inclusion
        if (empty($conf['output']) && !(empty($conf['marker.']['title']) && empty($conf['marker.']['description']))) {
            $out .= $this->pi_getLL('wecApiTemplateNotIncluded');
            \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('WEC Map API template not included on page id ' . $GLOBALS['TSFE']->id, 'wec_map', 3);
            return $out;
        }

        // check for WEC FE Map static template inclusion
        if (empty($conf['marker.']['title']) && empty($conf['marker.']['description'])) {
            $out .= $this->pi_getLL('pi2TemplateNotIncluded');
            \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('WEC FE User template not included on page id ' . $GLOBALS['TSFE']->id, 'wec_map', 3);
            return $out;
        }

        /* Initialize the Flexform and pull the data into a new object */
        $this->pi_initPIflexform();
        $piFlexForm = $this->cObj->data['pi_flexform'];

        // get config from flexform or TS. Flexforms take precedence.
        $width = $this->pi_getFFvalue($piFlexForm, 'mapWidth', 'default');
        empty($width) ? $width = $this->cObj->stdWrap($conf['width'], $conf['width.']):null;

        $height = $this->pi_getFFvalue($piFlexForm, 'mapHeight', 'default');
        empty($height) ? $height = $this->cObj->stdWrap($conf['height'], $conf['height.']):null;
        $this->height = $height;

        $userGroups = $this->pi_getFFvalue($piFlexForm, 'userGroups', 'default');
        empty($userGroups) ? $userGroups = $this->cObj->stdWrap($conf['userGroups'], $conf['userGroups.']):null;

        $userGroupsOrMode = $this->pi_getFFvalue($piFlexForm, 'userGroupsOrMode', 'default');
        empty($userGroupsOrMode) ? $userGroupsOrMode = $this->cObj->stdWrap($conf['userGroupsOrMode'], $conf['userGroupsOrMode.']):false;

        $pid = $this->pi_getFFvalue($piFlexForm, 'pid', 'default');
        empty($pid) ? $pid = $this->cObj->stdWrap($conf['pid'], $conf['pid.']):null;

        $mapControlSize = $this->pi_getFFvalue($piFlexForm, 'mapControlSize', 'mapControls');
        (empty($mapControlSize) || $mapControlSize == 'none') ? $mapControlSize = $this->cObj->stdWrap($conf['controls.']['mapControlSize'], $conf['controls.']['mapControlSize.']):null;

        $mapType = $this->pi_getFFvalue($piFlexForm, 'mapType', 'mapControls');
        empty($mapType) ? $mapType = $this->cObj->stdWrap($conf['controls.']['showMapType'], $conf['controls.']['showMapType.']):null;

        $googleEarth = $this->pi_getFFvalue($piFlexForm, 'googleEarth', 'mapControls');
        empty($googleEarth) ? $googleEarth = $this->cObj->stdWrap($conf['controls.']['showGoogleEarth'], $conf['controls.']['showGoogleEarth.']):null;

        $initialMapType = $this->pi_getFFvalue($piFlexForm, 'initialMapType', 'default');
        empty($initialMapType) ? $initialMapType = $this->cObj->stdWrap($conf['initialMapType'], $conf['initialMapType.']):null;

        $showZoom = $this->pi_getFFvalue($piFlexForm, 'showZoom', 'mapControls');
        empty($showZoom) ? $scale = $this->cObj->stdWrap($conf['controls.']['showZoom'], $conf['controls.']['showZoom.']):null;

        $scale = $this->pi_getFFvalue($piFlexForm, 'scale', 'mapControls');
        empty($scale) ? $scale = $this->cObj->stdWrap($conf['controls.']['showScale'], $conf['controls.']['showScale.']):null;

        $private = $this->pi_getFFvalue($piFlexForm, 'privacy', 'default');
        empty($private) ? $private = $this->cObj->stdWrap($conf['private'], $conf['private.']):null;

        $showDirs = $this->pi_getFFvalue($piFlexForm, 'showDirections', 'default');
        empty($showDirs) ? $showDirs = $this->cObj->stdWrap($conf['showDirections'], $conf['showDirections.']):null;
        $this->showDirections = $showDirs;

        $showWrittenDirs = $this->pi_getFFvalue($piFlexForm, 'showWrittenDirections', 'default');
        empty($showWrittenDirs) ? $showWrittenDirs = $this->cObj->stdWrap($conf['showWrittenDirections'], $conf['showWrittenDirections.']):null;

        $prefillAddress = $this->pi_getFFvalue($piFlexForm, 'prefillAddress', 'default');
        empty($prefillAddress) ? $prefillAddress = $this->cObj->stdWrap($conf['prefillAddress'], $conf['prefillAddress.']):null;

        $showRadiusSearch = $this->pi_getFFvalue($piFlexForm, 'showRadiusSearch', 'default');
        empty($showRadiusSearch) ? $showRadiusSearch = $this->cObj->stdWrap($conf['showRadiusSearch'], $conf['showRadiusSearch.']):null;

        $showSidebar = $this->pi_getFFvalue($piFlexForm, 'showSidebar', 'default');
        empty($showSidebar) ? $showSidebar = $this->cObj->stdWrap($conf['showSidebar'], $conf['showSidebar.']):null;
        $this->showSidebar = $showSidebar;

        $kml = $this->cObj->stdWrap($conf['kml'], $conf['kml.']);

        $centerLat = $this->cObj->stdWrap($conf['centerLat'], $conf['centerLat.']);

        $centerLong = $this->cObj->stdWrap($conf['centerLong'], $conf['centerLong.']);

        $zoomLevel = $this->pi_getFFvalue($piFlexForm, 'zoomLevel', 'mapConfig');
        empty($zoomLevel) || $zoomLevel == 0 ? $zoomLevel = $this->cObj->stdWrap($conf['zoomLevel'], $conf['zoomLevel.']):null;

        $maxAutoZoom = $this->cObj->stdWrap($conf['maxAutoZoom'], $conf['maxAutoZoom.']);

        $enableOverlappingMarkerManager = $this->cObj->stdWrap($conf['enableOverlappingMarkerManager'], $conf['enableOverlappingMarkerManager.']);
        $overlappingMarkerLatDev = $this->cObj->stdWrap($conf['overlappingMarkerLatDev'], $conf['overlappingMarkerLatDev.']);
        $overlappingMarkerLongDev = $this->cObj->stdWrap($conf['overlappingMarkerLongDev'], $conf['overlappingMarkerLongDev.']);

        $static = $this->cObj->stdWrap($conf['static.']['enabled'], $conf['static.']['enabled.']);
        $staticMode = $this->cObj->stdWrap($conf['static.']['mode'], $conf['static.']['mode.']);
        $staticExtent = $this->cObj->stdWrap($conf['static.']['extent'], $conf['static.']['extent.']);
        $staticUrlParam = $this->cObj->stdWrap($conf['static.']['urlParam'], $conf['static.']['urlParam.']);
        $staticLimit = $this->cObj->stdWrap($conf['static.']['limit'], $conf['static.']['limit.']);

        $mapName = $this->cObj->stdWrap($conf['mapName'], $conf['mapName.']);
        if (empty($mapName)) {
            $mapName = 'map' . $this->cObj->data['uid'];
        }
        $this->mapName = $mapName;

        /* Create the Map object */
        /** @var \JBartels\WecMap\MapService\Google\Map $map */
        $map = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JBartels\WecMap\MapService\Google\Map::class, null, $width, $height, $centerLat, $centerLong, $zoomLevel, $mapName);

        // get kml urls for each included record
        if (!empty($kml)) {
            $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
                ->getQueryBuilderForTable('tx_wecmap_external');
            $statement = $queryBuilder
                ->select('url')
                ->from('tx_wecmap_external')
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter(
                            \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $kml, true),
                            Connection::PARAM_INT_ARRAY
                        )
                    )
                )
                ->execute();
            while ($record = $statement->fetch()) {
                $link = trim($record['url']);
                $oldAbs = $GLOBALS['TSFE']->absRefPrefix;
                $GLOBALS['TSFE']->absRefPrefix = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
                $linkConf = [
                    'parameter' => $link,
                    'returnLast' => 'url'
                ];
                $link = $this->cObj->typolink('', $linkConf);
                $GLOBALS['TSFE']->absRefPrefix = $oldAbs;
                $map->addKML($link);
            }
            /*
                        $fileRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
                        $pi_uid = $this->cObj->data['uid'];
                        $fileObjects = $fileRepository->findByRelation('tt_content', 'kmlFile', $pi_uid);
                        // get file object information
                        foreach ($fileObjects as $key => $value) {
                            $map->addKML( $value->getPublicUrl() );
                        }
            */
        }

        // evaluate map controls based on configuration
        if ($mapControlSize == 'large'		// deprecated
          || $mapControlSize == 'small'		// deprecated
          || $mapControlSize == 'zoomonly'	// deprecated
          || $showZoom
          ) {
            $map->addControl('zoom');
        }

        $map->setMaxAutoZoom($maxAutoZoom);
        if ($enableOverlappingMarkerManager) {
            $map->addOption('enableOverlappingMarkerManager', true);
        }

        if ($scale) {
            $map->addControl('scale');
        }
        if ($mapType) {
            $map->addControl('mapType');
        }
        if ($initialMapType) {
            $map->setType($initialMapType);
        }
        if ($googleEarth) {
            $map->addControl('googleEarth');
        }
        if ($static) {
            $map->enableStatic($staticMode, $staticExtent, $staticUrlParam, $staticLimit);
        }

        // set up groups:
        // country
        if (is_array($conf['groups.']['country.']) || $private) {
            $countryConf = [];
            $countryConf['icon'] = $conf['groups.']['country.']['icon.'];
            $countryConf['minzoom'] = $this->cObj->stdWrap($conf['groups.']['country.']['zoom.']['min'], $conf['groups.']['country.']['zoom.']['min.']);
            $countryConf['maxzoom'] = $this->cObj->stdWrap($conf['groups.']['country.']['zoom.']['max'], $conf['groups.']['country.']['zoom.']['max.']);
            // country icon, if configured
            if (!empty($countryConf['icon']['imagepath'])
              || !empty($countryConf['icon']['imagepath.'])
              ) {
                $map->addMarkerIcon($countryConf['icon'], $this->cObj);
            } else {
                $countryConf['icon']['iconID'] ? null : $countryConf['icon']['iconID'] = null;
            }
            $showCountries = true;
        } else {
            $showCountries = false;
        }

        // city
        if (is_array($conf['groups.']['city.']) || $private) {
            $cityConf = [];
            $cityConf['icon'] = $conf['groups.']['city.']['icon.'];
            $cityConf['minzoom'] = $this->cObj->stdWrap($conf['groups.']['city.']['zoom.']['min'], $conf['groups.']['city.']['zoom.']['min.']);
            $cityConf['maxzoom'] = $this->cObj->stdWrap($conf['groups.']['city.']['zoom.']['max'], $conf['groups.']['city.']['zoom.']['max.']);
            // city icon, if configured
            if (!empty($cityConf['icon']['imagepath'])
              || !empty($cityConf['icon']['imagepath.'])
              ) {
                $map->addMarkerIcon($cityConf['icon'], $this->cObj);
            } else {
                $cityConf['icon']['iconID'] ? null : $cityConf['icon']['iconID'] = null;
            }
            $showCities = true;
        } else {
            $showCities = false;
        }

        // single
        $singleConf = [];
        $singleConf['icon'] = $conf['groups.']['single.']['icon.'];
        $singleConf['minzoom'] = $this->cObj->stdWrap($conf['groups.']['single.']['zoom.']['min'], $conf['groups.']['single.']['zoom.']['min.']);
        $singleConf['maxzoom'] = $this->cObj->stdWrap($conf['groups.']['single.']['zoom.']['max'], $conf['groups.']['single.']['zoom.']['max.']);

        // country icon, if configured
        if (!empty($singleConf['icon']['imagepath'])
          || !empty($singleConf['icon']['imagepath.'])
          ) {
            $map->addMarkerIcon($singleConf['icon'], $this->cObj);
        } else {
            $singleConf['icon']['iconID'] ? null : $singleConf['icon']['iconID'] = null;
        }

        // check whether to show the directions tab and/or prefill addresses and/or written directions
        if ($showDirs && $showWrittenDirs && $prefillAddress) {
            $map->enableDirections(true, $mapName . '_directions');
        }
        if ($showDirs && $showWrittenDirs && !$prefillAddress) {
            $map->enableDirections(false, $mapName . '_directions');
        }
        if ($showDirs && !$showWrittenDirs && $prefillAddress) {
            $map->enableDirections(true);
        }
        if ($showDirs && !$showWrittenDirs && !$prefillAddress) {
            $map->enableDirections();
        }

        // process radius search
        if ($showRadiusSearch) {

            // check for POST vars for our map. If there are any, proceed.
            $pRadius = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST($mapName . '_radius'));

            if (!empty($pRadius)) {
                $pAddress    = strip_tags(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST($mapName . '_address'));
                $pCity       = strip_tags(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST($mapName . '_city'));
                $pState      = strip_tags(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST($mapName . '_state'));
                $pZip        = strip_tags(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST($mapName . '_zip'));
                $pCountry    = strip_tags(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST($mapName . '_country'));
                $pKilometers = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST($mapName . '_kilometers'));

                $data = [
                    'street' => $pAddress,
                    'city'	=> $pCity,
                    'state' => $pState,
                    'zip' => $pZip,
                    'country' => $pCountry
                ];

                $desc = \JBartels\WecMap\Utility\Shared::render($data, $conf['defaultdescription.']);
                $map->addMarkerIcon($conf['homeicon.'], $this->cObj);
                $map->addMarkerByAddress($pAddress, $pCity, $pState, $pZip, $pCountry, '', $desc, 0, 18, 'homeicon');
                $map->setCenterByAddress($pAddress, $pCity, $pState, $pZip, $pCountry);
                $map->setRadius($pRadius, $pKilometers);
            }
        }

        $streetField  = \JBartels\WecMap\Utility\Shared::getAddressField('fe_users', 'street');
        $cityField    = \JBartels\WecMap\Utility\Shared::getAddressField('fe_users', 'city');
        $stateField   = \JBartels\WecMap\Utility\Shared::getAddressField('fe_users', 'state');
        $zipField     = \JBartels\WecMap\Utility\Shared::getAddressField('fe_users', 'zip');
        $countryField = \JBartels\WecMap\Utility\Shared::getAddressField('fe_users', 'country');

        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getQueryBuilderForTable('fe_users');
        $queryBuilder
            ->select('*')
            ->from('fe_users');

        // if a user group was set, make sure only those users from that group
        // will be selected in the query
        $whereUserGroups = $userGroups ?
            \JBartels\WecMap\Utility\Shared::getListQueryFromCSV('usergroup', $userGroups, $queryBuilder, $userGroupsOrMode ? 'OR' : 'AND') :
            null;

        // if a storage folder pid was specified, filter by that
        $wherePid = $pid ?
            \JBartels\WecMap\Utility\Shared::getListQueryFromCSV('pid', $pid, $queryBuilder, 'OR') :
            null;

        // Build resulting WHERE-clause
        $queryBuilder->where($whereUserGroups, $wherePid);

        // additional condition set by TypoScript
        $additionalWhere = $this->cObj->stdWrap($conf['table.']['additionalWhere'], $conf['table.']['additionalWhere.']);
        if ($additionalWhere) {
            $queryBuilder->andWhere($additionalWhere);
        }

        // sorting
        $orderBy = $this->cObj->stdWrap($conf['table.']['orderBy'], $conf['table.']['orderBy.']);
        if ($orderBy) {
            \JBartels\WecMap\Utility\Shared::setOrderBy($queryBuilder, $orderBy);
        }

        // limit
        $limit = $this->cObj->stdWrap($conf['table.']['limit'], $conf['table.']['limit.']);
        if ($limit) {
            \JBartels\WecMap\Utility\Shared::setLimit($queryBuilder, $limit);
        }

        // create country and zip code array to keep track of which country and state we already added to the map.
        // the point is to create only one marker per country on a higher zoom level to not
        // overload the map with all the markers, and do the same with zip codes.
        $countries = [];
        $cities = [];

        /* Select all frontend users */
        $statement = $queryBuilder->execute();
        while ($row = $statement->fetch()) {

            // add check for country and use different field if empty
            // @TODO: make this smarter with TCA or something
            if (empty($row[$countryField]) && $countryField == 'static_info_country') {
                $countryField = 'country';
            } elseif (empty($row[$countryField]) && $countryField == 'country') {
                $countryField = 'static_info_country';
            }

            /* Only try to add marker if there's a city */
            if ($row[$cityField] != '') {

                // if we haven't added a marker for this country yet, do so.
                if ($showCountries && !in_array($row[$countryField], $countries) && !empty($row[$countryField])) {

                    // add this country to the array
                    $countries[] = $row[$countryField];

                    // combine title config to pass to render function
                    $title_conf = ['title' => $conf['marker.']['title'], 'title.' => $conf['marker.']['title.']];

                    // add a little info so users know what to do
                    $title = \JBartels\WecMap\Utility\Shared::render(['name' => $this->pi_getLL('country_zoominfo_title')], $title_conf);
                    $description = sprintf($this->pi_getLL('country_zoominfo_desc'), $row[$countryField]);

                    // add a marker for this country and only show it between the configured zoom level.
                    $map->addMarkerByAddress(null, $row[$cityField], $row[$stateField], $row[$zipField], $row[$countryField], $title, $description, $countryConf['minzoom'], $countryConf['maxzoom'], $countryConf['icon']['iconID']);
                }

                // if we haven't added a marker for this zip code yet, do so.
                if ($showCities && !in_array($row[$cityField], $cities) && !empty($row[$cityField])) {

                    // add this country to the array
                    $cities[] = $row[$cityField];

                    // combine title config to pass to render function
                    $title_conf = ['title' => $conf['marker.']['title'], 'title.' => $conf['marker.']['title.']];

                    // add a little info so users know what to do
                    $title = \JBartels\WecMap\Utility\Shared::render(['name' => 'Info'], $title_conf);

                    $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
                        ->getQueryBuilderForTable('fe_users');
                    $queryBuilder
                        ->count('*')
                        ->from('fe_users');

                    // start where clause
                    $countWhere = [
                        $queryBuilder->expr()->eq($cityField, $queryBuilder->createNamedParameter($row[$cityField]))
                    ];
                    if ($zipField) {
                        $countWhere[] = $queryBuilder->expr()->eq($zipField, $queryBuilder->createNamedParameter($row[$zipField]));
                    }
                    if ($additionalWhere) {
                        $queryBuilder->andWhere('(' . $additionalWhere . ')');
                    }
                    $count = $queryBuilder->execute()->fetchColumn(0);

                    // extra processing if private is turned on
                    if ($private) {
                        $maxzoom = $singleConf['maxzoom'];
                        if ($count == 1) {
                            $description = sprintf($this->pi_getLL('citycount_si'), $row[$cityField]);
                        } else {
                            $description = sprintf($this->pi_getLL('citycount_pl'), $count, $row[$cityField]);
                        }
                    } else {
                        $maxzoom = $cityConf['maxzoom'];
                        $description = sprintf($this->pi_getLL('city_zoominfo_desc'), $row[$cityField]);
                    }

                    // add a marker for the city level and only show it
                    // either from city-min to single-max or city-min to city-max, depending on privacy settings
                    $marker = $map->addMarkerByAddress(null, $row[$cityField], $row[$stateField], $row[$zipField], $row[$countryField], $title, $description, $cityConf['minzoom'], $maxzoom, $cityConf['icon']['iconID']);
                }

                // make title and description
                $title = \JBartels\WecMap\Utility\Shared::render($row, $conf['marker.']['title.']);
                $description = \JBartels\WecMap\Utility\Shared::render($row, $conf['marker.']['description.']);

                // unless we are using privacy, add individual markers as well
                if (!$private) {
                    $marker = $map->addMarkerByAddress($row[$streetField], $row[$cityField], $row[$stateField], $row[$zipField], $row[$countryField], $title, $description, $singleConf['minzoom'], $singleConf['maxzoom'], $singleConf['icon']['iconID']);
                    if ($overlappingMarkerLatDev && $overlappingMarkerLongDev) {
                        $map->handleOverlappingMarker($marker, $overlappingMarkerLatDev, $overlappingMarkerLongDev);
                    }
                    $row['info_title'] = $title;
                    $row['info_description'] = $description;
                    $this->addSidebarItem($marker, $row);
                    $this->addDirectionsMenu($marker);
                }
            }
        }

        // gather all the content together
        $content = [];
        $content['map'] = $map->drawMap();
        if ($showRadiusSearch) {
            $content['addressForm'] = $this->getAddressForm();
        }
        if ($showWrittenDirs) {
            $content['directions']  = $this->getDirections();
        }
        if ($showSidebar) {
            $content['sidebar']     = $this->getSidebar();
        }

        // run all the content pieces through TS to assemble them
        $output = \JBartels\WecMap\Utility\Shared::render($content, $conf['output.']);

        return $this->pi_wrapInBaseClass($output);
    }

    /**
     * adds a sidebar item corresponding to the given marker.
     * Does so only if the sidebar is enabled.
     *
     * @return void
     **/
    public function addSidebarItem($marker, $data)
    {
        if (!($this->showSidebar && is_object($marker))) {
            return;
        }
        $data['onclickLink'] = $marker->getClickJS();
        $this->sidebarLinks[] = \JBartels\WecMap\Utility\Shared::render($data, $this->conf['sidebarItem.']);
    }

    public function getAddressForm()
    {
        $out = \JBartels\WecMap\Utility\Shared::render(['map_id' => $this->mapName], $this->conf['addressForm.']);
        return $out;
    }

    public function getDirections()
    {
        $out = \JBartels\WecMap\Utility\Shared::render(['map_id' => $this->mapName], $this->conf['directions.']);
        return $out;
    }

    public function getSidebar()
    {
        if (empty($this->sidebarLinks)) {
            return null;
        }

        $c = '';

        foreach ($this->sidebarLinks as $link) {
            $c .= $link;
        }
        $out = \JBartels\WecMap\Utility\Shared::render(['map_height' => $this->height, 'map_id' => $this->mapName, 'content' => $c], $this->conf['sidebar.']);

        return $out;
    }

    /**
     * adds a directions menu corresponding to the given marker.
     * Does so only if the showDirections is enabled.
     *
     * @return void
     **/
    public function addDirectionsMenu($marker)
    {
        if (!($this->showDirections && is_object($marker))) {
            return;
        }
        $marker->setDirectionsMenuConf($this->conf['directionsMenu.']);
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/pi2/class.tx_wecmap_pi2.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/pi2/class.tx_wecmap_pi2.php']);
}
