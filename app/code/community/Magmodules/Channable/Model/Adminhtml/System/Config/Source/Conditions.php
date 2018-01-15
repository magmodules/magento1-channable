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

class Magmodules_Channable_Model_Adminhtml_System_Config_Source_Conditions
{

    /**
     * Options array
     *
     * @var array
     */
    public $options = null;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = array(
                array(
                    'value' => '',
                    'label' => Mage::helper('channable')->__('')
                ),
                array(
                    'value' => 'eq',
                    'label' => Mage::helper('channable')->__('Equal')
                ),
                array(
                    'value' => 'neq',
                    'label' => Mage::helper('channable')->__('Not equal')
                ),
                array(
                    'value' => 'gt',
                    'label' => Mage::helper('channable')->__('Greater than')
                ),
                array(
                    'value' => 'gteq',
                    'label' => Mage::helper('channable')->__('Greater than or equal to')
                ),
                array(
                    'value' => 'lt',
                    'label' => Mage::helper('channable')->__('Less than')
                ),
                array(
                    'value' => 'lteg',
                    'label' => Mage::helper('channable')->__('Less than or equal to')
                ),
                array(
                    'value' => 'in',
                    'label' => Mage::helper('channable')->__('In')
                ),
                array(
                    'value' => 'nin',
                    'label' => Mage::helper('channable')->__('Not in')
                ),
                array(
                    'value' => 'like',
                    'label' => Mage::helper('channable')->__('Like')
                ),
                array(
                    'value' => 'nlike',
                    'label' => Mage::helper('channable')->__('Like')
                ),
                array(
                    'value' => 'empty',
                    'label' => Mage::helper('channable')->__('Empty')
                ),
                array(
                    'value' => 'not-empty',
                    'label' => Mage::helper('channable')->__('Not Empty')
                ),
                array(
                    'value' => 'finset',
                    'label' => Mage::helper('channable')->__('In Set')
                ),
            );
        }

        return $this->options;
    }
}