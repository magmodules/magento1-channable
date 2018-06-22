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
 * @author        Magmodules <info@magmodules.eu>
 * @copyright     Copyright (c) 2018 (http://www.magmodules.eu)
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Magmodules_Channable_Block_Adminhtml_System_Config_Form_Field_Button
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @var Mage_Adminhtml_Helper_Data
     */
    public $helper;

    /**
     * @inheritdoc.
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('magmodules/channable/system/config/test_button.phtml');
        $this->helper = Mage::helper('adminhtml');
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxCheckUrl()
    {
        return $this->helper->getUrl('adminhtml/selftest/run');
    }

    /**
     * @return string
     */
    public function getFlatcheck()
    {
        /** @var Magmodules_Channable_Model_Channable $model */
        $model = Mage::getModel("channable/channable");

        /** @var Magmodules_Channable_Helper_Data $helper */
        $helper = Mage::helper("channable");

        try {
            $flatProduct = Mage::getStoreConfig('catalog/frontend/flat_catalog_product');
            $bypassFlat = Mage::getStoreConfig('channable_connect/generate/bypass_flat');

            if ($flatProduct && !$bypassFlat) {
                $storeId = $helper->getStoreIdConfig();
                $nonFlatAttributes = $helper->checkFlatCatalog($model->getFeedAttributes($storeId, 'flatcheck'));
                if (!empty($nonFlatAttributes)) {
                    return sprintf(
                        '<span class="channable-flat" %s>%s</span>',
                        'onclick="javascript:testModule(); return false;"',
                        $helper->__('Possible data issue(s) found!')
                    );
                }
            }
        } catch (\Exception $e) {
            $helper->addToLog('checkFlat', $e->getMessage());
        }

        return null;
    }

    
    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(
                array(
                    'id'      => 'test_check_button',
                    'label'   => $this->helper('adminhtml')->__('Run'),
                    'onclick' => 'javascript:testModule(); return false;'
                )
            );

        return $button->toHtml();
    }
}