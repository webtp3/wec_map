<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JBartels\WecMap\MapService;

/**
 * Main class for the wec_map extension.  This class sits between the various
 * frontend plugins and address lookup service to render map data.
 *
 */
class Marker
{
    public $index;

    public $latitude;
    public $longitude;
    public $minzoom = 0;
    public $maxzoom;
    public $title;
    public $description;
    public $color;
    public $strokeColor;
    public $mapName;
    public $map = null;
    public $iconID;
    public $groupId = -1;
    public $directionsMenuConf;

    /**
     * Constructor stub. See map_service classes for more details on the marker
     * constructor.
     *
     * @return void
     **/
    public function __construct()
    {
    }

    /**
     * Getter for internal index for this marker.
     *
     * @return int index of the marker
     **/
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Getter for the marker title.
     *
     * @return string title of the marker
     **/
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Getter for marker description
     *
     * @return string description of the marker
     **/
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Setter for the marker title.
     *
     * @return void
     **/
    public function setTitle($newTitle)
    {
        $this->title = $newTitle;
    }

    /**
     * Setter for marker description
     *
     * @return void
     **/
    public function setDescription($newDesc)
    {
        $this->description = $newDesc;
    }

    /**
     * Getter for marker color
     *
     * @return string marker color
     **/
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Getter for the marker stroke color
     *
     * @return string marker stroke color
     **/
    public function getStrokeColor()
    {
        return $this->strokeColor;
    }

    /**
     * Getter for the latitude
     *
     * @return float latitude
     **/
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Getter for the longitude
     *
     * @return float longitude
     **/
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Setter for map name this marker is a part of
     *
     * @return void
     **/
    public function setMapName($mapName)
    {
        $this->mapName = $mapName;
    }

    /**
     * set the id of the group this marker belongs to
     *
     * @return void
     **/
    public function setGroupId($id)
    {
        $this->groupId = $id;
    }

    /**
     * sets the minimum zoom level this marker is displayed on
     *
     * @return void
     **/
    public function setMinzoom($zoom)
    {
        $this->minzoom = $zoom;
    }

    /**
     * sets the maximum zoom level this marker is displayed on
     *
     * @return void
     **/
    public function setMaxzoom($zoom)
    {
        $this->maxzoom = $zoom;
    }

    /**
     * Setter for map name this marker is a part of
     *
     * @return void
     **/
    public function setDirectionsMenuConf($conf)
    {
        $this->directionsMenuConf = $conf;
    }
}

//if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_marker.php']) {
//    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_marker.php']);
//}
