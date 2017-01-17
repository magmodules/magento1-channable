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

class Magmodules_Channable_Model_Adminhtml_System_Config_Source_Conditions
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $type = array();
        $type[] = array('value' => '', 'label' => Mage::helper('channable')->__(''));
        $type[] = array('value' => 'eq', 'label' => Mage::helper('channable')->__('Equal'));
        $type[] = array('value' => 'neq', 'label' => Mage::helper('channable')->__('Not equal'));
        $type[] = array('value' => 'gt', 'label' => Mage::helper('channable')->__('Greater than'));
        $type[] = array('value' => 'gteq', 'label' => Mage::helper('channable')->__('Greater than or equal to'));
        $type[] = array('value' => 'lt', 'label' => Mage::helper('channable')->__('Less than'));
        $type[] = array('value' => 'lteg', 'label' => Mage::helper('channable')->__('Less than or equal to'));
        $type[] = array('value' => 'in', 'label' => Mage::helper('channable')->__('In'));
        $type[] = array('value' => 'nin', 'label' => Mage::helper('channable')->__('Not in'));
        $type[] = array('value' => 'like', 'label' => Mage::helper('channable')->__('Like'));
        $type[] = array('value' => 'empty', 'label' => Mage::helper('channable')->__('Empty'));
        $type[] = array('value' => 'not-empty', 'label' => Mage::helper('channable')->__('Not Empty'));

        return $type;
    }

}