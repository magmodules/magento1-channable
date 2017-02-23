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
 * @copyright     Copyright (c) 2017 (http://www.magmodules.eu)
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */

class Magmodules_Channable_Adminhtml_ChannableController extends Mage_Adminhtml_Controller_Action
{

    /**
     * addToFlat contoller action
     */
    public function addToFlatAction()
    {
        $attributes = Mage::getModel("channable/channable")->getFeedAttributes();
        $nonFlatAttributes = Mage::helper('channable')->checkFlatCatalog($attributes);

        foreach ($nonFlatAttributes as $key => $value) {
            Mage::getModel('catalog/resource_eav_attribute')->load($key)
                ->setUsedInProductListing(1)
                ->save();
        }

        $msg = Mage::helper('channable')->__('Attributes added to Flat Catalog, please reindex Product Flat Data.');
        Mage::getSingleton('adminhtml/session')->addSuccess($msg);

        $this->_redirect('adminhtml/system_config/edit/section/channable');
    }

    /**
     * Token create / update function
     */
    public function createTokenAction()
    {
        $oldToken = Mage::getModel('core/config_data')->getCollection()
            ->addFieldToFilter('path', 'channable/connect/token')
            ->addFieldToFilter('scope_id', 0)
            ->addFieldToFilter('scope', 'default')
            ->getFirstItem()
            ->getValue();

        $token = '';
        $chars = str_split("abcdefghijklmnopqrstuvwxyz0123456789");
        for ($i = 0; $i < 32; $i++) {
          $token .= $chars[array_rand($chars)];
        }

        Mage::getModel('core/config')->saveConfig('channable/connect/token', Mage::helper('core')->encrypt($token));

        if (!empty($oldToken)) {
            $msg = 'New Token created, please update Channable Dashboard with this new token';
        } else {
            $msg = 'New Token created, please link your account using the auto update';
        }

        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('channable')->__($msg));
        $this->_redirect('adminhtml/system_config/edit/section/channable');
    }

    /**
     * @return mixed
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/channable/channable');
    }

}