<?php

/*
 * This file is part of the web-tp3/wec_map.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JBartels\WecMap\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Module 'WEC Map Admin' for the 'wec_map' extension.
 *
 */
class MapAdministrationBackendModule
{

    /*************************************************************************
     *
     * 		AJAX functions
     *
     ************************************************************************/

    /**
     * [Describe function...]
     *
     * @param	[type]		$$params: ...
     * @param	[type]		$ajaxObj: ...
     * @return	[type]		...
     */
    public function ajaxDeleteAll(ServerRequestInterface $request, ResponseInterface $response)
    {
        \JBartels\WecMap\Utility\Cache::deleteAll();
        $response->getBody()->write(json_encode([ 'status' => 'ok' ]));
        return $response;
    }

    public function ajaxDeleteSingle(ServerRequestInterface $request, ResponseInterface $response)
    {
        $hash = $request->getParsedBody()['record'];
        \JBartels\WecMap\Utility\Cache::deleteByUID($hash);  // $hash is escaped in deleteByUID()
        $response->getBody()->write(json_encode([ 'status' => 'ok' ]));
        return $response;
    }

    public function ajaxSaveRecord(ServerRequestInterface $request, ResponseInterface $response)
    {
        $hash = $request->getParsedBody()['record'];
        $latitude = floatval($request->getParsedBody()['latitude']);
        $longitude = floatval($request->getParsedBody()['longitude']);

        \JBartels\WecMap\Utility\Cache::updateByUID($hash, $latitude, $longitude);   // $hash is escaped in updateByUID()
        $response->getBody()->write(json_encode([ 'status' => 'ok' ]));
        return $response;
    }

    public function ajaxBatchGeocode(ServerRequestInterface $request, ResponseInterface $response)
    {
        $batchGeocode = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JBartels\WecMap\Utility\BatchGeocode::class);

        // add all tables to check which ones need geocoding and do it
        $batchGeocode->addAllTables();
        $batchGeocode->geocode();

        $processedAddresses = $batchGeocode->getProcessedAddresses();
        $geocodedAddresses = $batchGeocode->getGeocodedAddresses();
        $totalAddresses = $batchGeocode->getRecordCount();

        $response->getBody()->write(json_encode([
            'geocoded' => $geocodedAddresses,
            'processed' => $processedAddresses,
            'total' => $totalAddresses
        ]));
        return $response;
    }

    public function ajaxListRecords(ServerRequestInterface $request, ResponseInterface $response)
    {
        // fetch all cached addresses
        $records = \JBartels\WecMap\Utility\Cache::getAllAddresses();
        $response->getBody()->write(json_encode($records));
        return $response;
    }
}
