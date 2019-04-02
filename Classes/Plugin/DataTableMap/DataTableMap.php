<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JBartels\WecMap\Plugin\DataTableMap;

/**
 * Simple frontend plugin for displaying an address on a map.
 *
 */
class DataTableMap extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    public $prefixId = 'tx_wecmap_pi3';		// Same as class name
    public $scriptRelPath = 'Classes/Plugin/DataTableMap/DataTableMap.php';	// Path to this script relative to the extension dir.
    public $extKey = 'wec_map';	// The extension key.
    public $pi_checkCHash = true;

    /**
     * Draws a Google map based on an address entered in a Flexform.
     * @param	array		Content array.
     * @param	array		Conf array.
     * @return	string	HTML / Javascript representation of a Google map.
     */
    public function main($content, $conf)
    {
        $this->conf=$conf;
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL('EXT:wec_map/Resources/Private/Languages/Plugin/DataTableMap/locallang.xlf');

        // check for WEC Table Map static template inclusion
        if (empty($conf['templateIncluded'])) {
            $out .= $this->pi_getLL('pi3TemplateNotIncluded');
            \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('WEC Map API template not included on page id ' . $GLOBALS['TSFE']->id, 'wec_map', 3);
            return $out;
        }

        // check for WEC Map API static template inclusion
        if (empty($conf['output']) && !empty($conf['templateIncluded'])) {
            $out .= $this->pi_->getLL('wecApiTemplateNotIncluded');
            \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('WEC Table Map template not included on page id ' . $GLOBALS['TSFE']->id, 'wec_map', 3);
            return $out;
        }

        /* Initialize the Flexform and pull the data into a new object */
        $this->pi_initPIflexform();
        $piFlexForm = $this->cObj->data['pi_flexform'];

        // get config from flexform or TS. Flexforms take precedence.
        $width = $this->pi_getFFvalue($piFlexForm, 'mapWidth', 'mapConfig');
        empty($width) ? $width = $this->cObj->stdWrap($conf['width'], $conf['width.']):null;

        $height = $this->pi_getFFvalue($piFlexForm, 'mapHeight', 'mapConfig');
        empty($height) ? $height = $this->cObj->stdWrap($conf['height'], $conf['height.']):null;
        $this->height = $height;

        $pid = $this->pi_getFFvalue($piFlexForm, 'pid', 'default');
        empty($pid) ? $pid = $this->cObj->stdWrap($conf['pid'], $conf['pid.']):null;

        $recursive = $this->pi_getFFvalue($piFlexForm, 'recursive', 'default');
        empty($recursive) ? $recursive = $this->cObj->stdWrap($conf['recursive'], $conf['recursive.']):0;

        $mapControlSize = $this->pi_getFFvalue($piFlexForm, 'mapControlSize', 'mapControls');
        (empty($mapControlSize) || $mapControlSize == 'none') ? $mapControlSize = $this->cObj->stdWrap($conf['controls.']['mapControlSize'], $conf['controls.']['mapControlSize.']):null;

        $mapType = $this->pi_getFFvalue($piFlexForm, 'mapType', 'mapControls');
        empty($mapType) ? $mapType = $this->cObj->stdWrap($conf['controls.']['showMapType'], $conf['controls.']['showMapType.']):null;

        $googleEarth = $this->pi_getFFvalue($piFlexForm, 'googleEarth', 'mapControls');
        empty($googleEarth) ? $googleEarth = $this->cObj->stdWrap($conf['controls.']['showGoogleEarth'], $conf['controls.']['showGoogleEarth.']):null;

        $initialMapType = $this->pi_getFFvalue($piFlexForm, 'initialMapType', 'mapConfig');
        empty($initialMapType) ? $initialMapType = $this->cObj->stdWrap($conf['initialMapType'], $conf['initialMapType.']):null;

        $showZoom = $this->pi_getFFvalue($piFlexForm, 'showZoom', 'mapControls');
        empty($showZoom) ? $scale = $this->cObj->stdWrap($conf['controls.']['showZoom'], $conf['controls.']['showZoom.']):null;

        $scale = $this->pi_getFFvalue($piFlexForm, 'scale', 'mapControls');
        empty($scale) ? $scale = $this->cObj->stdWrap($conf['controls.']['showScale'], $conf['controls.']['showScale.']):null;

        $showDirs = $this->pi_getFFvalue($piFlexForm, 'showDirections', 'mapConfig');
        empty($showDirs) ? $showDirs = $this->cObj->stdWrap($conf['showDirections'], $conf['showDirections.']):null;
        $this->showDirections = $showDirs;

        $showWrittenDirs = $this->pi_getFFvalue($piFlexForm, 'showWrittenDirections', 'mapConfig');
        empty($showWrittenDirs) ? $showWrittenDirs = $this->cObj->stdWrap($conf['showWrittenDirections'], $conf['showWrittenDirections.']):null;

        $prefillAddress = $this->pi_getFFvalue($piFlexForm, 'prefillAddress', 'mapConfig');
        empty($prefillAddress) ? $prefillAddress = $this->cObj->stdWrap($conf['prefillAddress'], $conf['prefillAddress.']):null;

        $showRadiusSearch = $this->pi_getFFvalue($piFlexForm, 'showRadiusSearch', 'mapConfig');
        empty($showRadiusSearch) ? $showRadiusSearch = $this->cObj->stdWrap($conf['showRadiusSearch'], $conf['showRadiusSearch.']):null;

        $showSidebar = $this->pi_getFFvalue($piFlexForm, 'showSidebar', 'mapConfig');
        empty($showSidebar) ? $showSidebar = $this->cObj->stdWrap($conf['showSidebar'], $conf['showSidebar.']):null;
        $this->showSidebar = $showSidebar;

        $tables = $this->pi_getFFvalue($piFlexForm, 'tables', 'default');
        empty($tables) ? $tables = $conf['tables']:null;
        if (!empty($tables)) {
            $tables = explode(',', $tables);
        }

        $kml = $this->pi_getFFvalue($piFlexForm, 'kml', 'default');
        empty($kml) ? $kml = $this->cObj->stdWrap($conf['kml'], $conf['kml.']):null;

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
        if ($kml > 0) {
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
                            \TYPO3\CMS\Core\Database\Connection::PARAM_INT_ARRAY
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

        if (!empty($pid)) {
            $pidList = $this->pi_getPidList($pid, $recursive);
            $pidWhere = 'pid IN (' . $pidList . ')';
        } else {
            $pidWhere = '1=1';
        }

        // there are two ways of buiding the SQL query:
        // 1. from the data given via flexform
        // 2. all manually from TS
        // So we check whether it's set via TS, and if not we use FF data
        if (empty($conf['tables.'])) {
            if (is_array($tables)) {
                foreach ($tables as $table) {
                    $tconf   = $conf['flexformTables.'][$table . '.'];

                    $where   = $this->cObj->stdWrap($tconf['where'], $tconf['where.']);
                    $select  = $this->cObj->stdWrap($tconf['select'], $tconf['select.']);
                    $join    = $this->cObj->stdWrap($tconf['join'], $tconf['join.']);
                    $orderBy = $this->cObj->stdWrap($tconf['orderBy'], $tconf['orderBy.']);
                    $groupBy = $this->cObj->stdWrap($tconf['groupBy'], $tconf['groupBy.']);
                    $limit   = $this->cObj->stdWrap($tconf['limit'], $tconf['limit.']);

                    if (empty($where)) {
                        $where = '1=1';
                    }

                    if ($join) {
                        $from = $table . ' ' . $join;
                    } else {
                        $from = $table;
                    }

                    if (empty($select)) {
                        $select = '*';
                    }

                    $where .= ' AND ' . $pidWhere . $this->cObj->enableFields($table);

                    $res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($select, $from, $where, $groupBy, $orderBy, $limit);

                    // add icon if configured, else see if we just have an iconID
                    // and use that. We assume the icon is added somewhere else.
                    if (!empty($tconf['icon.']['imagepath'])
                      || !empty($tconf['icon.']['imagepath.'])
                      ) {
                        $map->addMarkerIcon($tconf['icon.'], $this->cObj);
                    } else {
                        $tconf['icon.']['iconID'] ? null : $tconf['icon.']['iconID'] = null;
                    }

                    foreach ($res as $key => $data) {
                        // get title and description
                        if (empty($tconf)) {
                            $conf['table'] = $table;
                            list($title, $desc) = $this->getTitleAndDescription($conf, $data);
                            $data['info_title'] = $title;
                            $data['info_description'] = $desc;
                            $marker = $map->addMarkerByTCA($table, $data['uid'], $title, $desc);
                            $tconf = [];
                        } else {
                            $tconf['table'] = $table;
                            list($title, $desc) = $this->getTitleAndDescription($tconf, $data);
                            $data['info_title'] = $title;
                            $data['info_description'] = $desc;

                            $marker = $map->addMarkerByTCA($table, $data['uid'], $title, $desc, 0, 18, $tconf['icon.']['iconID']);
                        }

                        if ($overlappingMarkerLatDev && $overlappingMarkerLongDev) {
                            $map->handleOverlappingMarker($marker, $overlappingMarkerLatDev, $overlappingMarkerLongDev);
                        }

                        // build parameters to pass to the hook
                        $params = ['table' => $table, 'data' => $data, 'markerObj' => &$marker, 'conf' => $tconf ];
                        $this->processHook($params);

                        $this->addSidebarItem($marker, $data);
                        $this->addDirectionsMenu($marker);
                    }
                }
            }
        } else {
            if (is_array($conf['tables.'])) {
                foreach ($conf['tables.'] as $table => $tconf) {
                    $where   = $this->cObj->stdWrap($tconf['where'], $tconf['where.']);
                    $select  = $this->cObj->stdWrap($tconf['select'], $tconf['select.']);
                    $table   = $this->cObj->stdWrap($tconf['table'], $tconf['table.']);
                    $join    = $this->cObj->stdWrap($tconf['join'], $tconf['join.']);
                    $orderBy = $this->cObj->stdWrap($tconf['orderBy'], $tconf['orderBy.']);
                    $groupBy = $this->cObj->stdWrap($tconf['groupBy'], $tconf['groupBy.']);
                    $limit   = $this->cObj->stdWrap($tconf['limit'], $tconf['limit.']);

                    if (empty($where)) {
                        $where = '1=1';
                    }

                    if ($join) {
                        $from = $table . ' ' . $join;
                    } else {
                        $from = $table;
                    }

                    if (empty($select)) {
                        $select = '*';
                    }

                    $where .= ' AND ' . $pidWhere . $this->cObj->enableFields($table);

                    $res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($select, $from, $where, $groupBy, $orderBy, $limit);

                    // add icon if configured, else see if we just have an iconID
                    // and use that. We assume the icon is added somewhere else.
                    if (!empty($tconf['icon.']['imagepath'])
                      || !empty($tconf['icon.']['imagepath.'])
                      ) {
                        $map->addMarkerIcon($tconf['icon.'], $this->cObj);
                    } else {
                        $tconf['icon.']['iconID'] ? null : $tconf['icon.']['iconID'] = null;
                    }

                    foreach ($res as $key => $data) {
                        // get title and description
                        list($title, $desc) = $this->getTitleAndDescription($tconf, $data);
                        $data['info_title'] = $title;
                        $data['info_description'] = $desc;
                        $marker = $map->addMarkerByTCA($table, $data['uid'], $title, $desc, 0, 18, $tconf['icon.']['iconID']);

                        if ($overlappingMarkerLatDev && $overlappingMarkerLongDev) {
                            $map->handleOverlappingMarker($marker, $overlappingMarkerLatDev, $overlappingMarkerLongDev);
                        }

                        // build parameters to pass to the hook
                        $params = ['table' => $table, 'data' => $data, 'markerObj' => &$marker, 'conf' => $tconf];
                        $this->processHook($params);

                        $this->addSidebarItem($marker, $data);
                        $this->addDirectionsMenu($marker);
                    }
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
            $content['directions'] = $this->getDirections();
        }
        if ($showSidebar) {
            $content['sidebar'] = $this->getSidebar();
        }

        // run all the content pieces through TS to assemble them
        $output = \JBartels\WecMap\Utility\Shared::render($content, $conf['output.']);

        return $this->pi_wrapInBaseClass($output);
    }

    /**
     * Processes the hook
     *
     * @return void
     **/
    public function processHook(&$hookParameters)
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecmap_pi3']['markerHook'])) {
            $hooks =& $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecmap_pi3']['markerHook'];
            $hookReference = null;
            foreach ($hooks as $hookFunction) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hookFunction, $hookParameters, $hookReference);
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': Called hook. Markers may have been changed.', 'wec_map_api', 2);
            }
        }
    }

    /**
     * returns an array with title and description
     *
     * @return array
     **/
    public function getTitleAndDescription($conf, $data)
    {

        // merge the table into the data
        $data = array_merge($data, ['table' => $conf['table']]);

        // process title only if TS config is present
        if (!empty($conf['title.'])) {
            $title = \JBartels\WecMap\Utility\Shared::render($data, $conf['title.'], $conf['table']);
        } else {
            $data['name'] = $this->getRecordTitle($conf['table'], $data);
            $title = \JBartels\WecMap\Utility\Shared::render($data, $this->conf['defaulttitle.'], $conf['table']);
        }

        // process description also only if TS config is present,
        // otherwise display the address
        if (!empty($conf['description.'])) {
            $desc = \JBartels\WecMap\Utility\Shared::render($data, $conf['description.'], $conf['table']);
        } else {
            $ad = [];
            $ad['street']  = $data[\JBartels\WecMap\Utility\Shared::getAddressField($conf['table'], 'street')];
            $ad['city']    = $data[\JBartels\WecMap\Utility\Shared::getAddressField($conf['table'], 'city')];
            $ad['state']   = $data[\JBartels\WecMap\Utility\Shared::getAddressField($conf['table'], 'state')];
            $ad['zip']     = $data[\JBartels\WecMap\Utility\Shared::getAddressField($conf['table'], 'zip')];
            $ad['country'] = $data[\JBartels\WecMap\Utility\Shared::getAddressField($conf['table'], 'country')];

            $desc = \JBartels\WecMap\Utility\Shared::render($ad, $this->conf['defaultdescription.'], $conf['table']);
        }

        return [$title, $desc];
    }

    public function getRecordTitle($table, $row)
    {
        if (is_array($GLOBALS['TCA'][$table])) {

            // If configured, call userFunc
            if ($GLOBALS['TCA'][$table]['ctrl']['label_userFunc']) {
                $params['table'] = $table;
                $params['row'] = $row;
                $params['title'] = '';

                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($GLOBALS['TCA'][$table]['ctrl']['label_userFunc'], $params, $this);
                $t = $params['title'];
            } else {

                // No userFunc: Build label
                $t = $row[$GLOBALS['TCA'][$table]['ctrl']['label']];

                if ($GLOBALS['TCA'][$table]['ctrl']['label_alt'] && ($GLOBALS['TCA'][$table]['ctrl']['label_alt_force'] || !strcmp($t, ''))) {
                    $altFields=\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], 1);
                    $tA=[];
                    $tA[]=$t;
                    if ($GLOBALS['TCA'][$table]['ctrl']['label_alt_force']) {
                        foreach ($altFields as $fN) {
                            $t = trim(strip_tags($row[$fN]));
                            if (!empty($t)) {
                                $tA[] = $t;
                            }
                        }
                        $t=implode(', ', $tA);
                    }
                }
            }

            return $t;
        }
        return '';
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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/pi3/class.tx_wecmap_pi3.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/pi3/class.tx_wecmap_pi3.php']);
}
