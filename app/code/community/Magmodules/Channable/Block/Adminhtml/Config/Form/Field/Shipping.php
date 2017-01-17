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

class Magmodules_Channable_Block_Adminhtml_Config_Form_Field_Shipping
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

    protected $_renders = array();

    /**
     * Magmodules_Channable_Block_Adminhtml_Config_Form_Field_Shipping constructor.
     */
    public function __construct()
    {
        $layout = Mage::app()->getFrontController()->getAction()->getLayout();
        $rendererCoutries = $layout->createBlock(
            'channable/adminhtml_config_form_renderer_select',
            '',
            array('is_render_to_js_template' => true)
        );

        $rendererCoutries->setOptions(
            Mage::getModel('channable/adminhtml_system_config_source_countries')->toOptionArray()
        );

        $this->addColumn(
            'country', array(
                'label'    => Mage::helper('channable')->__('Country'),
                'style'    => 'width:120px',
                'renderer' => $rendererCoutries
            )
        );

        $this->addColumn(
            'price_from', array(
                'label' => Mage::helper('channable')->__('Price From'),
                'style' => 'width:40px',
            )
        );
        $this->addColumn(
            'price_to', array(
                'label' => Mage::helper('channable')->__('Price To'),
                'style' => 'width:40px',
            )
        );
        $this->addColumn(
            'cost', array(
                'label' => Mage::helper('channable')->__('Cost'),
                'style' => 'width:40px',
            )
        );

        $this->_renders['country'] = $rendererCoutries;

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('channable')->__('Add Option');
        parent::__construct();
    }

    /**
     * @param Varien_Object $row
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {
        foreach ($this->_renders as $key => $render) {
            $row->setData('option_extra_attr_' . $render->calcOptionHash($row->getData($key)), 'selected="selected"');
        }
    }

}