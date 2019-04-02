<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/**
 * Test case for WEC Map
 *
 * WARNING: Never ever run a unit test like this on a live site!
 *
 *
 */
class tx_wecmap_autozoom_testcase extends tx_phpunit_testcase
{
    public function test_default_max_auto_zoom_is_15()
    {
        $map = $this->createMap();
        $map->autoCenterAndZoom();

        $this->assertEquals(15, $map->zoom);
    }

    public function test_max_auto_zoom_setter_with_7()
    {
        $map = $this->createMap();
        $map->setMaxAutoZoom(7);
        $map->autoCenterAndZoom();

        $this->assertEquals(7, $map->zoom);
    }

    public function test_max_auto_zoom_is_15_if_setter_empty()
    {
        $map = $this->createMap();
        $map->setMaxAutoZoom();
        $map->autoCenterAndZoom();
        $this->assertEquals(15, $map->zoom);
    }

    public function createMap()
    {
        //include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wec_map').'map_service/google/class.tx_wecmap_map_google.php');
        $map = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_wecmap_map_google', null, 500, 500, 39.842286, -96.855469, null, 'name');
        $map->addMarkerByLatLong(39.842286, -96.855469);
        return $map;
    }
}
