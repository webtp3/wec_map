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
class tx_wecmap_get_address_field_testcase extends tx_phpunit_testcase
{
    public function test_get_street_field_for_fe_users()
    {
        $street = tx_wecmap_shared::getAddressField('fe_users', 'street');
        $this->assertEquals('address', $street);
    }

    public function test_get_zip_field_for_fe_users()
    {
        $street = tx_wecmap_shared::getAddressField('fe_users', 'zip');
        $this->assertEquals('zip', $street);
    }

    public function test_get_state_field_for_fe_users()
    {
        $state = tx_wecmap_shared::getAddressField('fe_users', 'state');
        $this->assertEquals('zone', $state);
    }

    public function test_get_country_field_for_fe_users()
    {
        $country = tx_wecmap_shared::getAddressField('fe_users', 'country');
        $this->assertEquals('static_info_country', $country);
    }

    public function test_get_city_field_for_fe_users()
    {
        $city = tx_wecmap_shared::getAddressField('fe_users', 'city');
        $this->assertEquals('city', $city);
    }

    public function __construct()
    {
        global $TCA;
        //include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wec_map').'class.tx_wecmap_shared.php');
    }
}
