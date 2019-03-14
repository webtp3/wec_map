<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JBartels\WecMap\MapService;

/**
 * Defines a group of markers to display on the map. This class is the interface
 * to the GMarkerManager. Every map has one or more groups, which has one or more
 * markers. Every marker belongs to one group, and every group belongs to one map.
 *
 */
class MarkerGroup
{
    public $markers;			// array of marker objects
    public $markerCount = 0; 	// convenience variable with number of markers = sizeof($markers);
    public $id;				// unique identifier of this group
    public $mapName;			// the name of the map this group belongs to
    public $minzoom;			// min zoom level for this group
    public $maxzoom;			// max zoom level for this group

    /**
     * PHP5 constructor
     *
     * @return void
     **/
    public function __construct($id, $minzoom, $maxzoom)
    {
        $this->id = $id;
        $this->minzoom = $minzoom;
        $this->maxzoom = $maxzoom;
    }

    /**
     * returns the js array
     *
     * @return array javascript content
     **/
    public function drawMarkerJS()
    {
        $jsContent = [];

        foreach ($this->markers as $key => $marker) {
            if ($this->directions) {
                $jsContent[] = $marker->writeJSwithDirections();
                $jsContent[] = $marker->writeCreateMarkerJS();
            } else {
                $jsContent[] = $marker->writeJS();
                $jsContent[] = $marker->writeCreateMarkerJS();
            }
        }
        if (count($jsContent)) {
            $jsContent[] = 'WecMap.addMarkersToManager("' . $this->mapName . '", ' . $this->id . ', ' . $this->minzoom . ', ' . $this->maxzoom . ');';
        }
        return $jsContent;
    }

    /**
     * adds a marker object to this group
     *
     * @return void
     **/
    public function addMarker(&$markerObj)
    {
        $markerObj->setMinZoom($this->minzoom);
        $markerObj->setMaxZoom($this->maxzoom);
        $markerObj->setMapName($this->mapName);
        $markerObj->setGroupId($this->id);
        $this->markers[] = &$markerObj;
        \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($this->mapName . ': -----adding marker - start----', 'wec_map_api', -1, [
            'id' => $markerObj->getIndex(),
            'minzoom' => $this->minzoom,
            'maxzoom' => $this->maxzoom,
            'group' => $this->id,
            'count' => $this->markerCount,
            'title' => implode(',', $markerObj->getTitle()),
            'desc' => implode(',', $markerObj->getDescription())
        ]);
        $this->markerCount++;
    }

    /**
     * return min zoom level
     *
     * @return int
     **/
    public function getMinZoom()
    {
        return $this->minzoom;
    }

    /**
     * return max zoom
     *
     * @return int
     **/
    public function getMaxZoom()
    {
        return $this->maxzoom;
    }

    /**
     * return the number of markers in this group
     *
     * @return int
     **/
    public function getMarkerCount()
    {
        return $this->markerCount;
    }

    /**
     * set map name
     *
     * @return void
     **/
    public function setMapName($name)
    {
        $this->mapName = $name;
    }

    /**
     * Enables directions
     *
     * @return void
     **/
    public function setDirections($dirs=true)
    {
        $this->directions = $dirs;
    }

    /**
     * Returns whether this group has any markers
     *
     * @return boolean
     **/
    public function hasMarkers()
    {
        if ($this->markerCount > 0) {
            return true;
        } else {
            return false;
        }
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_markergroup.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_map/class.tx_wecmap_markergroup.php']);
}
