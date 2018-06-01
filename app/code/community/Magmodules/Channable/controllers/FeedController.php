<?php
/**
 * Magmodules.eu - http://www.magmodules.eu
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magmodules.eu so we can send you a copy immediately.
 *
 * @category      Magmodules
 * @package       Magmodules_Channable
 * @author        Magmodules <info@magmodules.eu)
 * @copyright     Copyright (c) 2018 (http://www.magmodules.eu)
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */

class Magmodules_Channable_FeedController extends Mage_Core_Controller_Front_Action
{

    /**
     * @var Magmodules_Channable_Helper_Data
     */
    public $helper;
    /**
     * @var Magmodules_Channable_Model_Channable
     */
    public $model;

    /**
     *
     */
    public function _construct()
    {
        $this->helper = Mage::helper('channable');
        $this->model = Mage::getModel('channable/channable');
    }

    /**
     *
     */
    public function getAction()
    {
        $storeId = $this->getRequest()->getParam('store');
        $enabled = Mage::getStoreConfig('channable/connect/enabled', $storeId);

        if ($enabled) {
            $code = $this->getRequest()->getParam('code');
            $page = $this->getRequest()->getParam('page', 1);
            if ($storeId && $code) {
                if ($code == $this->helper->getToken()) {
                    try {
                        $timeStart = microtime(true);
                        /** @var Mage_Core_Model_App_Emulation $appEmulation */
                        $appEmulation = Mage::getSingleton('core/app_emulation');
                        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
                        Mage::app()->loadAreaPart(
                            Mage_Core_Model_App_Area::AREA_GLOBAL,
                            Mage_Core_Model_App_Area::PART_EVENTS
                        )->loadArea(Mage_Core_Model_App_Area::AREA_FRONTEND);
                        if ($feed = $this->model->generateFeed($storeId, $timeStart, $page)) {
                            if ($this->getRequest()->getParam('array')) {
                                $this->getResponse()->setBody(Zend_Debug::dump($feed, null, false));
                            } else {
                                $this->getResponse()
                                    ->clearHeaders()
                                    ->setHeader('Content-type', 'application/json', true)
                                    ->setHeader('Cache-control', 'no-cache', true)
                                    ->setBody(json_encode($feed));
                            }
                        }

                        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
                    } catch (Exception $e) {
                        Mage::logException($e);
                        $this->getResponse()
                            ->clearHeaders()
                            ->setHeader('Content-type', 'application/json', true)
                            ->setHeader('Cache-control', 'no-cache', true)
                            ->setBody(json_encode(array('error' => $e->getMessage())));
                    }
                }
            }
        }
    }

}
