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

class Magmodules_Channable_Model_Adminhtml_System_Config_Source_Configurable
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
            $storeId = Mage::helper('channable')->getStoreIdConfig();
            $attributes = Mage::getModel("channable/channable")->getFeedAttributes($storeId, 'config');
            $attributesSkip = array('id', 'parent_id', 'price', 'availability', 'is_in_stock', 'qty', 'status', 'visibility');
            $att = array();
            foreach ($attributes as $key => $attribute) {
                if (!in_array($key, $attributesSkip) && !empty($key)) {
                    $label = !empty($attribute['label']) ? $attribute['label'] : $key;
                    $att[$label] = array('value' => $key, 'label' => $label);
                }
            }

            $this->options = $att;
        }

        return $this->options;
    }
}