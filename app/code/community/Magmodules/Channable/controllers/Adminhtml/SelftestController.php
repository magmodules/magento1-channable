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
 * @package       Magmodules_Channableapi
 * @author        Magmodules <info@magmodules.eu>
 * @copyright     Copyright (c) 2018 (http://www.magmodules.eu)
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Magmodules_Channable_Adminhtml_SelftestController extends Mage_Adminhtml_Controller_Action
{

    /**
     * @var Magmodules_Channable_Helper_Selftest
     */
    public $helper;

    /**
     * Construct.
     */
    public function _construct()
    {
        $this->helper = Mage::helper('channable/selftest');
        parent::_construct();
    }

    /**
     *
     */
    public function runAction()
    {
        $results = $this->helper->runTests();
        $msg = implode('<br/>', $results);
        Mage::app()->getResponse()->setBody($msg);
    }

    /**
     * @return mixed
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/system/config/channable');
    }
}