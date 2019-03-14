<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JBartels\WecMap\MapService\Google;

/**
 * Marker implementation for the Google Maps mapping service.
 *
 */
class Marker extends \JBartels\WecMap\MapService\Marker
{
    public $index;

    public $latitude;
    public $longitude;

    public $title;
    public $description;
    public $color;
    public $strokeColor;
    public $prefillAddress;
    public $tabLabels;
    public $iconID;

    public $lang;
    public $LOCAL_LANG;
    /** @var $langService \TYPO3\CMS\Lang\LanguageService */
    public $langService;

    /**
     * Constructor for the Google Maps marker class.
     *
     * @access	public
     * @param	int		Index within the overall array of markers.
     * @param	float		Latitude of the marker location.
     * @param	float		Longitude of the marker location.
     * @param	string		Title of the marker.
     * @param	string		Description of the marker.
     * @param 	bool		Sets whether the directions address should be prefilled with logged in user's address
     * @param	array 		Labels used on tabs. Optional.
     * @param	string		Unused for Google Maps.
     * @param	string		Unused for Google Maps.
     * @return	none
     */
    public function __construct($index, $latitude, $longitude, $title, $description, $prefillAddress = false, $tabLabels=null, $color='0xFF0000', $strokeColor='0xFFFFFF', $iconID='')
    {

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
        $this->LOCAL_LANG = $this->langService->includeLLFile('EXT:wec_map/Resources/Private/Languages/MapService/Google/locallang.xlf', false);

        $this->index = $index;
        $this->tabLabels = [$this->getLL('info')];
        if (is_array($tabLabels)) {
            $this->tabLabels = array_merge($this->tabLabels, $tabLabels);
        }

        $this->prefillAddress = $prefillAddress;

        $this->title = [];
        $this->description = [];

        if (is_array($title)) {
            foreach ($title as $value) {
                $this->title[] = $value;
            }
        } else {
            $this->title[] = $title;
        }

        if (is_array($description)) {
            foreach ($description as $value) {
                $this->description[] = $this->filterNL2BR($value);
            }
        } else {
            $this->description[] = $this->filterNL2BR($description);
        }

        $this->color = $color;
        $this->strokeColor = $strokeColor;

        $this->latitude = $latitude;
        $this->longitude = $longitude;

        $this->iconID = $iconID;

        $this->isDraggable = false;
    }

    public function getLL($index)
    {
        return $this->langService->getLLL($index, $this->LOCAL_LANG);
    }

    /**
     * Creates the Javascript to add a marker to the page.
     *
     * @access public
     * @return	string	The Javascript to add a marker to the page.
     */
    public function writeJS()
    {
        $markerContent = [];
        foreach ($this->tabLabels as $index => $label) {
            $markerContent[] = json_encode($this->title[$index]) . '+' . json_encode($this->description[$index], JSON_HEX_APOS);
        }
        $tabLabels = [];
        foreach ($this->tabLabels as $index => $label) {
            $tabLabels[] = json_encode($label, JSON_HEX_APOS);
        }

        if ($this->directions) {
            $data = [ 'map_id' => $this->mapName,
                           'groupId' => $this->groupId,
                           'index' => $this->index,
                           'address' => $this->getUserAddress(),
                           'latitude' => $this->latitude,
                           'longitude' => $this->longitude,
                           'dirTitle' => htmlspecialchars(strip_tags($this->title[0]))
                         ];

            if (is_array($this->directionsMenuConf)) {
                $markerContent[0] .= '+' . json_encode(\JBartels\WecMap\Utility\Shared::render($data, $this->directionsMenuConf), JSON_HEX_APOS);
            } else {
                // Workaround for EXT:cal
                // get default directionsMenu
                $directionsMenuConf = $GLOBALS['TSFE']->tmpl->setup['tx_wecmap_api.']['directionsMenu.'];
                if (is_array($directionsMenuConf)) {
                    $markerContent[0] .= '+' . json_encode(\JBartels\WecMap\Utility\Shared::render($data, $directionsMenuConf), JSON_HEX_APOS);
                }
            }
        }

        return '
WecMap.addBubble( "' . $this->mapName . '", ' . $this->groupId . ', ' . $this->index . ', [' . implode(',', $tabLabels) . '], [' . implode(',', $markerContent) . ']);';
    }

    /**
     * Wrapper method that makes sure directions are properly displayed
     *
     * @return string 	the javascript to add the marker
     **/
    public function writeJSwithDirections()
    {
        $this->directions = true;
        return $this->writeJS();
    }

    /**
     * undocumented function
     *
     * @return void
     **/
    public function writeCreateMarkerJS()
    {
        if (empty($this->title[0]) && $this->directions) {
            $this->title[0] = 'Address';
        }
        $js = 'WecMap.addMarker( '
                               . "'" . $this->mapName . "', " . $this->index . ', '
                               . '[' . $this->latitude . ',' . $this->longitude . '], '
                               . "'" . $this->iconID . "', "
                               . json_encode(htmlspecialchars(strip_tags($this->title[0])), JSON_HEX_APOS) . ', '
                               . $this->groupId . ', '
                               . json_encode($this->getUserAddress(), JSON_HEX_APOS)
                             . ');';
        if ($this->isDraggable) {
            $js .= "WecMap.setDraggable('" . $this->mapName . "', " . $this->groupId . ', ' . $this->index . ', true);';
        }
        return $js;
    }

    /**
     * undocumented function
     *
     * @return void
     **/
    public function setDraggable($flag)
    {
        $this->isDraggable = $flag;
    }

    /**
     * adds a new tab to the marker
     *
     * @return void
     **/
    public function addTab($tabLabel, $title, $description)
    {
        if (!is_array($this->title)) {
            $temp = $this->title;
            $this->title = [$temp];
        }

        if (!is_array($this->description)) {
            $temp = $this->description;
            $this->description = [$temp];
        }

        if (!is_array($this->tabLabels)) {
            $this->tabLabels = [$this->getLL('info')];
        }

        $this->tabLabels[] = $tabLabel;
        $this->title[] = $title;
        $this->description[] = $description;
        \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': manually adding tab to marker ' . $this->index . ' with title ' . $title, 'wec_map_api');
    }

    /**
     * Gets the address of the user who is currently logged in
     *
     * @return string
     **/
    public function getUserAddress()
    {
        if ($this->prefillAddress) {
            if (TYPO3_MODE == 'FE') {
                $feuser_id = $GLOBALS['TSFE']->fe_user->user['uid'];

                if (!empty($feuser_id)) {
                    $table = 'fe_users';
                    $streetField  = \JBartels\WecMap\Utility\Shared::getAddressField($table, 'street');
                    $cityField    = \JBartels\WecMap\Utility\Shared::getAddressField($table, 'city');
                    $stateField   = \JBartels\WecMap\Utility\Shared::getAddressField($table, 'state');
                    $zipField     = \JBartels\WecMap\Utility\Shared::getAddressField($table, 'zip');
                    $countryField = \JBartels\WecMap\Utility\Shared::getAddressField($table, 'country');

                    $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
                        ->getQueryBuilderForTable('fe_users');
                    $statement = $queryBuilder
                        ->select('*')
                        ->from('fe_users')
                        ->where(
                            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($feuser_id, \PDO::PARAM_INT))
                        )
                        ->execute();
                    $row = $statement->fetch();

                    return $row[$streetField] . ', ' . $row[$cityField] . ', ' . $row[$stateField] . ' ' . $row[$zipField] . ', ' . $row[$countryField];
                }
            } else {
            }
        }
        return '';
    }

    /**
     * Returns the javascript function call to center on this marker
     *
     * @return String
     **/
    public function getClickJS()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': adding marker ' . $this->index . '(' . strip_tags($this->title[0]) . strip_tags($this->description[0]) . ') to sidebar', 'wec_map_api');
        return 'WecMap.jumpTo(\'' . $this->mapName . '\', ' . $this->groupId . ', ' . $this->index . ', ' . $this->calculateClickZoom() . ');';
    }

    public function getOpenInfoWindowJS()
    {
        return 'WecMap.openInfoWindow("' . $this->mapName . '", ' . $this->groupId . ', ' . $this->index . ');';
    }

    public function getInitialOpenInfoWindowJS()
    {
        return 'WecMap.openInitialInfoWindow("' . $this->mapName . '", ' . $this->groupId . ', ' . $this->index . ');';
    }

    /**
     * calculates the optimal zoom level for the click
     * we want to keep the zoom level around $zoom, but will
     * choose the max if the marker is only visible under $zoom,
     * or the min if it's only shown over $zoom.
     * @return integer
     **/
    public function calculateClickZoom()
    {
        $zoom = 14;
        if ($zoom < $this->minzoom) {
            $zoom = $this->minzoom;
        } elseif ($zoom > $this->maxzoom) {
            $zoom = $this->maxzoom;
        }
        return $zoom;
    }

    /**
     * Converts newlines to <br/> tags.
     *
     * @access	private
     * @param	string		The input string to filtered.
     * @return	string		The converted string.
     */
    public function filterNL2BR($input)
    {
        $order  = ["\r\n", "\n", "\r"];
        $replace = '<br />';
        return str_replace($order, $replace, $input);
    }

    /**
     * strip newlines
     *
     * @access	private
     * @param	string		The input string to filtered.
     * @return	string		The converted string.
     */
    public function stripNL($input)
    {
        $order  = ["\r\n", "\n", "\r"];
        $replace = '<br />';
        return str_replace($order, $replace, $input);
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/map_service/google/class.tx_wecmap_marker_google.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/map_service/google/class.tx_wecmap_marker_google.php']);
}
