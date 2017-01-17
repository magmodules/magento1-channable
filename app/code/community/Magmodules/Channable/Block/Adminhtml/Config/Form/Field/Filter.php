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

class Magmodules_Channable_Block_Adminhtml_Config_Form_Field_Filter
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

    protected $_renders = array();

    /**
     * Magmodules_Channable_Block_Adminhtml_Config_Form_Field_Filter constructor.
     */
    public function __construct()
    {
        $layout = Mage::app()->getFrontController()->getAction()->getLayout();
        $rendererAttributes = $layout->createBlock(
            'channable/adminhtml_config_form_renderer_select',
            '',
            array('is_render_to_js_template' => true)
        );
        $rendererAttributes->setOptions(
            Mage::getModel('channable/adminhtml_system_config_source_attribute')->toOptionArray()
        );

        $rendererConditions = $layout->createBlock(
            'channable/adminhtml_config_form_renderer_select',
            '',
            array('is_render_to_js_template' => true)
        );

        $rendererConditions->setOptions(
            Mage::getModel('channable/adminhtml_system_config_source_conditions')->toOptionArray()
        );

        $this->addColumn(
            'attribute', array(
                'label'    => Mage::helper('channable')->__('Attribute'),
                'style'    => 'width:100px',
                'renderer' => $rendererAttributes
            )
        );

        $this->addColumn(
            'condition', array(
                'label'    => Mage::helper('channable')->__('Condition'),
                'style'    => 'width:100px',
                'renderer' => $rendererConditions
            )
        );

        $this->addColumn(
            'value', array(
                'label' => Mage::helper('channable')->__('Value'),
                'style' => 'width:100px',
            )
        );

        $this->_renders['attribute'] = $rendererAttributes;
        $this->_renders['condition'] = $rendererConditions;

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('channable')->__('Add Filter');
        parent::__construct();
    }

    /**
     * @param Varien_Object $row
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {
        foreach ($this->_renders as $key => $render) {
            $row->setData(
                'option_extra_attr_' . $render->calcOptionHash($row->getData($key)),
                'selected="selected"'
            );
        }
    }
}