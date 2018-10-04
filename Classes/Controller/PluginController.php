<?php
namespace JBartels\WecMap\Controller;

/***
 *
 * This file is part of the "wec_map" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Thomas Ruta &lt;email@thomasruta.de&gt;, R&amp;P IT Consulting GmbH
 *
 ***/

/**
 * PluginController
 */
class PluginController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * Initializes the view before invoking an action method.
     *
     * Override this method to solve assign variables common for all actions
     * or prepare the view in another way before the action is called.
     *
     * @param ViewInterface $view The view to be initialized
     *
     * @api
     */
    protected function initializeView(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view)
    {
      //  $this->view = $view;
        // Typoscript-Konfiguration fuer entsprechendes Template holen
        $templateRootPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->settings['templateRootPath']);

        // Template-Pfad festlegen bzw. entsprechend anpassen
        $templatePathAndFilename = $templateRootPath . 'Plugin/Pi1.html';
        if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('extbase')) < 8007000) {

            $this->view->setTemplatePathAndFilename($templatePathAndFilename);
        } else {
            $layoutRootPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->settings['layoutRootPath']);
            $partialRootPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->settings['partialRootPath']);
            //   $this->view->setRenderingContext()
            $this->view->setLayoutRootPaths(array($layoutRootPath));
            $this->view->setPartialRootPaths(array($partialRootPath));
            $this->view->setTemplatePathAndFilename($templatePathAndFilename);
        }
    }
    /**
     * action list
     * 
     * @return void
     */
    public function listAction()
    {
        $cObj = $this->configurationManager->getContentObject();

        $this->conf = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

        $Plugins = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JBartels\WecMap\Plugin\DataTableMap::class)->main($cObj,$this->conf);
        $this->view->assign('Plugins', $Plugins );
    }
    /**
     * action list
     *
     * @return void
     */
    public function pi1Action()
    {
        $cObj = $this->configurationManager->getContentObject();

        $this->conf = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

        $Plugins = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JBartels\WecMap\Plugin\SimpleMap::class)->main($cObj,$this->conf);
        $this->view->assign('Plugins', $Plugins );
    }
    /**
     * action list
     *
     * @return void
     */
    public function pi2Action()
    {
        $cObj = $this->configurationManager->getContentObject();

        $this->conf = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

        $Plugins = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JBartels\WecMap\Plugin\FEUserMap::class)->main($cObj,$this->conf);
        $this->view->assign('Plugins', $Plugins );
    }
    /**
     * action list
     *
     * @return void
     */
    public function pi3Action()
    {
        $cObj = $this->configurationManager->getContentObject();

        $this->conf = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

        $Plugins = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JBartels\WecMap\Plugin\DataTableMap::class)->main($cObj,$this->conf);
        $this->view->assign('Plugins', $Plugins);
       // $this->view->assign('output', $this->conf["output"] );

        //$this->view->assign('Plugins', $Plugins );

    }
}
