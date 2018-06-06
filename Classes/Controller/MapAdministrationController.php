<?php
namespace JBartels\WecMap\Controller;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use JBartels\WecMap\Module\FEUserMap;

/**
 * Controller which handles actions related to Asset.
 */
class MapAdministrationController extends ActionController {
	/**
	 * @var array
	 */
	protected $iconMapping = array(
			'gfx/zoom.gif' => 'actions-search',
			'gfx/edit2.gif' => 'actions-open',
			'gfx/garbage.gif' => 'actions-delete',
			'gfx/napshot.gif' => 'actions-document-save',
			'gfx/clip_copy.gif' => 'actions-edit-copy',
			'gfx/up.gif' => 'actions-move-up',
			'gfx/new_el.gif' => 'actions-document-new',
	);
	/**
	 * @var null|\TYPO3\CMS\Core\Imaging\IconFactory
	 */
	protected $iconFactory = null;
	/**
	 * @param string $icon
	 * @param string $iconSize
	 * @param string $backPath
	 * @param string $title
	 * @param string $alt
	 *
	 * @return string
	 */
	protected function getIcon($icon, $iconSize = '', $backPath = '', $title = '', $alt = '')
	{
		if ($this->typo3VersionMain === 6) {
			return $this->getIconByIconUtility($icon, $iconSize, $backPath, $title, $alt);
		} else {
			$icon = $this->iconMapping[$icon];
			return $this->getIconByIconFactory($icon);
		}
	}
	/**
	 * Interface of a view
	 *
	 * @return TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
	 */
	public $view;
	/**
	 * 
	 * 
	 */
	
	public $FEUserMap;
	/**
	 *
	 *
	 */
	
	public $content;
	/**
	 * Allows the widget template root path to be overridden via the framework configuration,
	 * e.g. plugin.tx_extension.view.widget.<WidgetViewHelperClassName>.templateRootPaths
	 *
	 * @param ViewInterface $view
	 * @return void
	 * @see \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController in TYPO3 CMS 7
	 */
	protected function setViewConfiguration(ViewInterface $view) {
		if ($this->request instanceof WidgetRequest) {
			$extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, $this->extensionName);
			$widgetViewHelperClassName = $this->request->getWidgetContext()->getWidgetViewHelperClassName();
			if (isset($extbaseFrameworkConfiguration['view']['widget'][$widgetViewHelperClassName])) {
				$configurationOverridden = $extbaseFrameworkConfiguration;
				$configurationOverridden['view'] = array_replace_recursive($configurationOverridden['view'], $configurationOverridden['view']['widget'][$widgetViewHelperClassName]);
				$this->configurationManager->setConfiguration($configurationOverridden);
				ActionController::setViewConfiguration($view);
				$this->configurationManager->setConfiguration($extbaseFrameworkConfiguration);
			}
		} else {
			ActionController::setViewConfiguration($view);
		}
	}
	/**
	 * Initializes the Module
	 * @return	void
	 */
	function indexAction()	{
		$this->setViewConfiguration($this->view);
		$this->iconFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\IconFactory');
		return $this->moduleContent();
		
	}
	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent()	{
	
		//case 1:
			$this->content.=$this->showMap();
			//break;
		
		//case 2:
			$this->content .= $this->mapSettings();
			//break;
		//switch((string)$this->MOD_SETTINGS['function'])	{
			//case 1:
				$this->content.=$this->geocodeAdmin();
			//	break;
			//case 2:
				$this->content.=$this->batchGeocode();
			//	break;
		//	case 3:
				$this->content.=$this->downloadJSFiles();
		//		break;
		//	case 4:
				$this->content.=$this->apiKeyAdmin();
		//		break;
		//}
		return $this->content;
	}
	function mapSettings() {
	
		if(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx-wecmap-mod1-submit')) {
	
			$scale = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx-wecmap-mod1-scale');
			if($scale == 'on') {
				$scale = 1;
			} else {
				$scale = 0;
			}
	
			$maptype = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx-wecmap-mod1-maptype');
			if($maptype == 'on') {
				$maptype = 1;
			} else {
				$maptype = 0;
			}
	
			$mapcontrolsize = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx-wecmap-mod1-mapcontrolzoom');
			if($controlzoom == 'on') {
				$controlzoom = 1;
			} else {
				$controlzoom = 0;
			}
	
			// build data array
			$data = array('scale' => $scale, 'maptype' => $maptype, 'controlzoom' => $controlzoom);
	
			// save to user config
			$GLOBALS['BE_USER']->pushModuleData('tools_txwecmapM2', $data);
		}
	
		// get module config
		$conf = $GLOBALS['BE_USER']->getModuleData('tools_txwecmapM2');
	
	
		// get config options
		$scale = $conf['scale'];
		$maptype = $conf['maptype'];
		$mapcontrolsize = $conf['mapcontrolsize'];	// deprecated;
		$controlzoom = $conf['controlzoom']
		|| ( mapcontrolsize != 'none' && !empty( $mapcontrolsize ) );	// deprecated
	
		$form = array();
		$form[] = '<table>';
	
		// scale option
		$form[] = '<tr>';
		$form[] = '<td><label for="tx-wecmap-mod1-scale">Show Scale:</label></td>';
		if($scale) {
			$form[] = '<td><input type="checkbox" name="tx-wecmap-mod1-scale" id="tx-wecmap-mod1-scale" checked="checked"/></td>';
		} else {
			$form[] = '<td><input type="checkbox" name="tx-wecmap-mod1-scale" id="tx-wecmap-mod1-scale" /></td>';
		}
		$form[] = '</tr>';
	
		// maptype option
		$form[] = '<tr>';
		$form[] = '<td><label for="tx-wecmap-mod1-maptype">Show Maptype:</label></td>';
		if($maptype) {
			$form[] = '<td><input type="checkbox" name="tx-wecmap-mod1-maptype" id="tx-wecmap-mod1-maptype" checked="checked"/></td>';
		} else {
			$form[] = '<td><input type="checkbox" name="tx-wecmap-mod1-maptype" id="tx-wecmap-mod1-maptype" /></td>';
		}
		$form[] = '</tr>';
	
		$form[] = '<tr>';
		$form[] = '<td><label for="tx-wecmap-mod1-controlzoom">Map Zoom Control:</label></td>';
		if($controlzoom) {
			$form[] = '<td><input type="checkbox" name="tx-wecmap-mod1-controlzoom" id="tx-wecmap-mod1-controlzoom" checked="checked"/></td>';
		} else {
			$form[] = '<td><input type="checkbox" name="tx-wecmap-mod1-controlzoom" id="tx-wecmap-mod1-controlzoom" /></td>';
		}
		$form[] = '</tr>';
	
	
		$form[] = '</table>';
		$form[] = '<input type="submit" name="tx-wecmap-mod1-submit" id="tx-wecmap-mod1-submit" value="Save" />';
	
	
		return implode(chr(10), $form);
	}
	
	/**
	 * Shows map
	 *
	 * @return String
	 **/
	function showMap() {
		global $LANG;
		/* Create the Map object */
		$width = 500;
		$height = 500;
		$conf = $GLOBALS['BE_USER']->getModuleData('tools_txwecmapM2');
	
	
		// get options
		$scale = $conf['scale'];
		$maptype = $conf['maptype'];
		$mapcontrolsize = $conf['mapcontrolsize'];	// deprecated;
		$controlzoom = $conf['controlzoom']
		|| ( mapcontrolsize != 'none' && !empty( $mapcontrolsize ) );	// deprecated
	
		$streetField  = \JBartels\WecMap\Utility\Shared::getAddressField('fe_users', 'street');
		$cityField    = \JBartels\WecMap\Utility\Shared::getAddressField('fe_users', 'city');
		$stateField   = \JBartels\WecMap\Utility\Shared::getAddressField('fe_users', 'state');
		$zipField     = \JBartels\WecMap\Utility\Shared::getAddressField('fe_users', 'zip');
		$countryField = \JBartels\WecMap\Utility\Shared::getAddressField('fe_users', 'country');
	
		#include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wec_map').'map_service/google/class.tx_wecmap_map_google.php');
		$map = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JBartels\WecMap\MapService\Google\Map::class, $apiKey, $width, $height);
	
		// evaluate map controls based on configuration
		if($controlzoom) $map->addControl('zoom');
		if($scale) $map->addControl('scale');
		if($maptype) $map->addControl('mapType');
		$map->enableDirections(false, 'directions');
	
		/* Select all frontend users */
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'fe_users', '');
	
		// create country and zip code array to keep track of which country and state we already added to the map.
		// the point is to create only one marker per country on a higher zoom level to not
		// overload the map with all the markers and do the same with zip codes.
		$countries = array();
		$cities = array();
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))) {
	
			// add check for country and use different field if empty
			// @TODO: make this smarter with TCA or something
			if(empty($row[$countryField]) && $countryField == 'static_info_country') {
				$countryField = 'country';
			} else if(empty($row[$countryField]) && $countryField == 'country') {
				$countryField = 'static_info_country';
			}
	
			/* Only try to add marker if there's a city */
			if($row[$cityField] != '') {
	
				// if we haven't added a marker for this country yet, do so.
				if(!in_array($row[$countryField], $countries) && !empty($row[$countryField])) {
	
					// add this country to the array
					$countries[] = $row[$countryField];
	
					// add a little info so users know what to do
					$title = '';
					$description = '<div class="description">'.sprintf($LANG->getLL('country_zoominfo_desc'), $row[$countryField]).'</div>';
	
					// add a marker for this country and only show it between zoom levels 0 and 2.
					$map->addMarkerByAddress(null, $row[$cityField], $row[$stateField], $row[$zipField], $row[$countryField], $title, $description, 0,2);
				}
	
	
				// if we haven't added a marker for this zip code yet, do so.
				if(!in_array($row[$cityField], $cities) && !empty($cityField)) {
	
					// add this country to the array
					$cities[] = $row[$cityField];
	
					// add a little info so users know what to do
					$title = '';
					$description = '<div class="description">'.$LANG->getLL('area_zoominfo_desc').'</div>';
	
					// add a marker for this country and only show it between zoom levels 0 and 2.
					$map->addMarkerByAddress(null, $row[$cityField], $row[$stateField], $row[$zipField], $row[$countryField], $title, $description, 3,7);
				}
	
				// make title and description
				$title = '<div style="font-size: 110%; font-weight: bold;">'.$row['name'].'</div>';
				$content = '<div>'.$row[$streetField].'<br />'.$row[$cityField].', '.$row[$stateField].' '.$row[$zipField].'<br />'. $row[$countryField].'</div>';
	
	
				// add all the markers starting at zoom level 3 so we don't crowd the map right away.
				// if private was checked, don't use address to geocode
				if($private) {
					$map->addMarkerByAddress(null, $row[$cityField], $row[$stateField], $row[$zipField], $row[$countryField], $title, $content, 8);
				} else {
					$map->addMarkerByAddress($row[$streetField], $row[$cityField], $row[$stateField], $row[$zipField], $row[$countryField], $title, $content, 8);
				}
			}
		}
	
		$content = $map->drawMap();
		$content .= '<div id="directions"></div>';
		return $content;
	}
	
	function returnEditLink($uid,$title) {
		$tablename = 'fe_users';
		$params = '&edit['.$tablename.']['.$uid.']=edit';
		$out .=    '<a href="#" onclick="'.
				\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params,$GLOBALS['BACK_PATH']).
				'">';
				$out .= $title;
				$out .= '<img'.$this->getIcon($GLOBALS['BACK_PATH'],'gfx/edit2.gif','width="11" height="12"').' title="Edit me" border="0" alt="" />';
				$out .= '</a>';
				return $out;
	}
	
	
	function linkSelf($addParams)	{
		return htmlspecialchars('index.php?id='.$this->pObj->id.'&showLanguage='.rawurlencode(strip_tags(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showLanguage'))).$addParams);
	}
	
	/**
	 * Rendering the encode-cache content
	 *
	 * @param	array		The Page tree data
	 * @return	string		HTML for the information table.
	 */
	function geocodeAdmin()	{
	
		$recordHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JBartels\WecMap\Module\MapAdministration\RecordHandler::class );
	
		global $LANG;
	
		$output   = $recordHandler->displaySearch();
		$output  .= $recordHandler->displayTable();
	
		return $output;
	}
	
	/*
	 * Admin module for setting Google Maps API Key.
	 * @return		string		HTML output of the module.
	 */
	function apiKeyAdmin() {
		global $TYPO3_CONF_VARS, $LANG;
	
		$domainmgr = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JBartels\WecMap\Utility\DomainMgr::class);
	
		$blankDomainValue = 'Enter domain....';
	
		$cmd = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cmd');
	
		switch($cmd) {
			case 'setkey' :
	
				// transform the POST array to our needs.
				// we then get a simple array in the form:
				// array('domain1', 'domain2', 'key1', 'key2'), etc.
				$post = $_POST;
				unset($post['cmd']);
				unset($post['SET']);
				unset($post['x']);
				unset($post['y']);
	
				$allDomains = $domainmgr->processPost($post);
	
				break;
	
			default :
				$allDomains = $domainmgr->getAllDomains();
				break;
		}
	
		$content = array();
		$content[] = '<style type="text/css" media="screen">input[type=image] {border: none; background: none;}</style>';
		$content[] = '<p style="margin-bottom:15px;">';
		$content[] = $LANG->getLL('apiInstructions');
		$content[] = '</p>';
	
		$content[] = '<form action="" method="POST">';
		$content[] = '<input name="cmd" type="hidden" value="setkey" />';
	
		$index = 0;
	
		// get number of entries that have a key
		$tempDomains = $allDomains;
		foreach( $tempDomains as $key => $value) {
			if(empty($value)) unset($tempDomains[$key]);
		}
		$number = count($tempDomains);
	
		foreach( $allDomains as $key => $value ) {
	
			// show the first summary text above all the already saved domains
			if($number != 0 && $index == 0) {
				$content[] = '<h1>Existing Domains</h1>';
				$content[] = '<p style="margin-bottom:15px;">';
				$content[] = $LANG->getLL('alreadySavedDomains');
				$content[] = '</p>';
			} else if ($number == $index) {
				$content[] = '<h1>Suggested Domains</h1>';
				$content[] = '<p style="margin-bottom:15px;">';
				$content[] = $LANG->getLL('suggestedDomains');
				$content[] = '</p>';
			}
	
			if($index < $number) {
				$deleteButton = '<input type="image" '.$this->getIcon($GLOBALS['BACK_PATH'],'gfx/garbage.gif','width="11" height="12"').' onclick="document.getElementById(\'key_'. $index .'\').value = \'\';" />';
			} else {
				$deleteButton = null;
			}
	
			$values = explode( '&', $value );
			$valuebrowser = $values[ 0 ];
			$valueserver = $values[ 1 ];
			$content[] = '<div class="domain-item" style="margin-bottom: 15px;">';
			$content[] = '<div style="width: 25em;"><strong>'. $key .'</strong> '. $deleteButton .'</div>';
	
			$content[] = '<input type="hidden" name="domain_'.$index.'" value="'. $key .'">';
	
			$content[] = '<div><label for="key_'. $index .'">'.$LANG->getLL('googleMapsBrowserApiKey').': </label>';
			$content[] = '<input style="width: 29em;" id="browserkey_'. $index .'" name="browserkey_'. $index .'" value="'.$valuebrowser.'" />';
	
			$content[] = '<label for="serverkey_'. $index .'">'.$LANG->getLL('googleMapsServerApiKey').': </label>';
			$content[] = '<input style="width: 29em;" id="serverkey_'. $index .'" name="serverkey_'. $index .'" value="'.$valueserver.'" /></div>';
	
			$content[] = '</div>';
			$index++;
		}
	
		$content[] = '<div id="adddomainbutton" style="margin-bottom: 15px;"><a href="#" onclick="document.getElementById(\'blank-domain\').style.display = \'block\'; document.getElementById(\'adddomainbutton\').style.display = \'none\'; document.getElementById(\'domain_'.$index.'\').value=\''. $blankDomainValue .'\';">Manually add a new API key for domain</a></div>';
		$content[] = '<div class="domain-item" id="blank-domain" style="margin-bottom: 15px; display: none;">';
		$content[] = '<div style="width: 35em;"><label style="display: none;" for="domain_'. $index .'">Domain: </label><input style="width: 12em;" id="domain_'. $index .'" name="domain_'. $index .'" value="" onfocus="this.value=\'\';"/> <input type="image" '.$this->getIcon($GLOBALS['BACK_PATH'],'gfx/garbage.gif','width="11" height="12"').' onclick="document.getElementById(\'key_'. $index .'\').value = \'\'; document.getElementById(\'blank-domain\').style.display =\'none\'; document.getElementById(\'adddomainbutton\').style.display = \'block\'; return false;" /></div>';
	
		$content[] = '<div><label for="browserkey_'. $index .'">'.$LANG->getLL('googleMapsBrowserApiKey').': </label>';
		$content[] = '<input style="width: 29em;" id="browserkey_'. $index .'" name="browserkey_'. $index .'" value="" />';
		$content[] = '<label for="serverkey_'. $index .'">'.$LANG->getLL('googleMapsServerApiKey').': </label>';
		$content[] = '<input style="width: 29em;" id="serverkey_'. $index .'" name="serverkey_'. $index .'" value="" /></div>';
	
		$content[] = '</div>';
	
		$content[] = '<input type="submit" value="'.$LANG->getLL('submit').'"/>';
		$content[] = '</form>';
	
		return implode(chr(10), $content);
	}
	
	/**
	 * Submodule for the batch geocoder.
	 *
	 * @return		string		HTML output.
	 */
	function batchGeocode() {
		global $LANG;
		$content = array();
	
		$batchGeocode = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JBartels\WecMap\Module\MapAdministration\BatchGeocode::class, 1);
		$batchGeocode->addAllTables();
		$totalAddresses = $batchGeocode->getRecordCount();
	
		$content[] = '<h3>'.$LANG->getLL('batchGeocode').'</h3>';
		$content[] = '<p>'.$LANG->getLL('batchInstructions').'</p>';
	
		$content[] = '<p style="margin-top:1em;">'.$LANG->getLL('batchTables').'</p>';
		$content[] = '<ul>';
		foreach($GLOBALS['TCA'] as $tableName => $tableContents) {
			if($tableContents['ctrl']['EXT']['wec_map']['isMappable']) {
				$title = $LANG->sL($tableContents['ctrl']['title']);
				$content[] = '<li>'.$title.'</li>';
			}
		}
		$content[] = '</ul>';
	
		$content[] = '<div id="status" style="margin-bottom: 5px; display:none;">';
		$content[] =   '<div id="bar" style="width:300px; height:20px; border:1px solid black">';
		$content[] =     '<div id="progress" style="width:0%; height:20px; background-color:red"></div>';
		$content[] =   '</div>';
		$content[] =   '<p>'.$LANG->getLL('processedStart').' <span id="processed">0</span> '.$LANG->getLL('processedMid').' '.$totalAddresses.'.</p>';
		$content[] = '</div>';
	
		$content[] = '<input id="startGeocoding" type="submit" value="'.$LANG->getLL('startGeocoding').'">';
	
		return implode(chr(10), $content);
	}
	/**
	 * Rendering the encode-cache content
	 *
	 * @param	array		The Page tree data
	 * @return	string		HTML for the information table.
	 */
	function downloadJSFiles()	{
		global $LANG;
	
		$cmd = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cmd');
	
		$content = array();
	
		switch($cmd) {
			case 'downloadJS' :
				$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wec_map']);
				$content[] = $this->download( 'https://' . $extConf['mmURL'], 'markermanager.js' );
				$content[] = $this->download( 'https://' . $extConf['ibURL'], 'infobubble.js' );
				$content[] = $this->download( 'https://' . $extConf['omURL'], 'oms.min.js' );
				$content[] = '<br />';
				break;
	
			default :
				break;
		}
	
		$content[] = '<style type="text/css" media="screen">input[type=image] {border: none; background: none;}</style>';
		$content[] = '<p style="margin-bottom:15px;">';
		$content[] = $LANG->getLL('downloadInstructions');
		$content[] = '</p>';
	
		$content[] = '<form action="" method="POST">';
		$content[] = '<input name="cmd" type="hidden" value="downloadJS" />';
		$content[] = '<input type="submit" value="'.$LANG->getLL('download').'"/>';
		$content[] = '</form>';
	
		return implode(chr(10), $content);
	}
	
	/**
	 * @param string $sourceUrl
	 * @param string $destFile
	 * @return string HTML
	 */
	protected function download($sourceUrl, $destFile)    {
		global $LANG;
	
		$destDir = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:wec_map/Resources/Public/JavaScript/ContribJS/');
	
		// Get file and cancel if not existing/accessible
		$remoteFileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL($sourceUrl);
		if ($remoteFileContent === FALSE) {
			return $LANG->getLL('downloadError') . $sourceUrl . '<br />';
		}
	
		// Create dir if not existing
		if (!file_exists($destDir)) {
			mkdir($destDir);
		}
	
		// Write content to disk
		$handle = fopen($destDir . $destFile, 'wb');
		fwrite($handle, $remoteFileContent);
		fclose($handle);
	
		return $LANG->getLL('downloadSuccess') . $destFile . '<br />';
	}
	/**
	 * @param string $icon
	 * @param string $iconSize
	 * @param string $backPath
	 * @param string $title
	 * @param string $alt
	 *
	 * @return string
	 */
	protected function getIconByIconUtility($icon, $iconSize, $backPath, $title, $alt)
	{
		$imgTag = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($backPath, $icon, $iconSize);
		if ($title !== '') {
			$imgTag .= 'title="' . htmlspecialchars($title) . '" ';
		}
		if ($alt !== '') {
			$imgTag .= 'alt="' . htmlspecialchars($alt) . '" ';
		}
		$imgTag .= '/>';
		return $imgTag;
	}
	/**
	 * @param string $icon
	 *
	 * @return string
	 */
	protected function getIconByIconFactory($icon) {
		$iconSize = \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL;
		return $this->iconFactory->getIcon($icon, $iconSize)->render();
	}
	

}
