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

class Magmodules_Channable_Model_Adminhtml_System_Config_Source_Attribute_Abstract
{

    /**
     * Options array
     *
     * @var array
     */
    public $options = array();

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $options[] = array('value' => '', 'label' => Mage::helper('channable')->__('-- None'));
            $options[] = $this->getAttributesArray();
            $relations = $this->getRelations();
            if (!empty($relations)) {
                $options[] = $relations;
            }

            $this->options = $options;
        }

        return $this->options;
    }

    /**
     * @return array
     */
    public function getAttributesArray()
    {
        $optionArray = $this->getExtraFields();
        $excludes = $this->getExludeAttributes();
        $backendTypes = $this->getBackendTypes();
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->setOrder('frontend_label', 'ASC')
            ->addFieldToFilter('backend_type', $backendTypes)
            ->addFieldToFilter('attribute_code', array('nin' => $excludes));

        foreach ($attributes as $attribute) {
            $optionArray[] = array(
                'value' => $attribute->getData('attribute_code'),
                'label' => $this->getLabel($attribute),
            );
        }

        usort(
            $optionArray, function ($a, $b) {
            return strcmp($a["label"], $b["label"]);
            }
        );

        return array(
            'label'         => Mage::helper('channable')->__('Atttibutes'),
            'value'         => $optionArray,
            'optgroup-name' => Mage::helper('channable')->__('Atttibutes')
        );
    }

    /**
     * @return array
     */
    public function getExtraFields()
    {
        $optionArray = array();

        $optionArray[] = array(
            'label' => Mage::helper('channable')->__('Product ID'),
            'value' => 'entity_id'
        );
        $optionArray[] = array(
            'label' => Mage::helper('channable')->__('Final Price'),
            'value' => 'final_price'
        );
        $optionArray[] = array(
            'label' => Mage::helper('channable')->__('Product Type'),
            'value' => 'type_id'
        );
        $optionArray[] = array(
            'label' => Mage::helper('channable')->__('Attribute Set ID'),
            'value' => 'attribute_set_id'
        );
        $optionArray[] = array(
            'label' => Mage::helper('channable')->__('Attribute Set Name'),
            'value' => 'attribute_set_name'
        );
        $optionArray[] = array(
            'label' => Mage::helper('channable')->__('Minumun Sales Quantity'),
            'value' => 'min_sale_qty'
        );
        $optionArray[] = array(
            'label' => Mage::helper('channable')->__('Backorders'),
            'value' => 'backorders'
        );
        return $optionArray;
    }

    /**
     * @return array
     */
    public function getRelations()
    {
        $optionArray = array();
        $optionArray[] = array(
            'label' => Mage::helper('channable')->__('Related Skus'),
            'value' => 'related_skus'
        );
        $optionArray[] = array(
            'label' => Mage::helper('channable')->__('Upsell Skus'),
            'value' => 'upsell_skus'
        );
        $optionArray[] = array(
            'label' => Mage::helper('channable')->__('Crosssell Skus'),
            'value' => 'crosssell_skus'
        );

        return array(
            'label'         => Mage::helper('channable')->__('Product Relations'),
            'value'         => $optionArray,
            'optgroup-name' => Mage::helper('channable')->__('Product Relations')
        );
    }

    /**
     * @return array
     */
    public function getExludeAttributes()
    {
        return array(
            'compatibility',
            'gallery',
            'installation',
            'language_support',
            'country_of_manufacture',
            'links_title',
            'current_version',
            'custom_design',
            'custom_layout_update',
            'gift_message_available',
            'image',
            'image_label',
            'media_gallery',
            'msrp_display_actual_price_type',
            'msrp_enabled',
            'options_container',
            'price_view',
            'page_layout',
            'samples_title',
            'sku_type',
            'tier_price',
            'url_key',
            'small_image',
            'small_image_label',
            'thumbnail',
            'thumbnail_label',
            'recurring_profile',
            'version_info',
            'category_ids',
            'has_options',
            'required_options',
            'url_path',
            'updated_at',
            'weight_type',
            'sku_type',
            'link_exist',
            'old_id',
            'price_type'
        );

    }

    /**
     * @return array
     */
    public function getBackendTypes()
    {
        return array('text', 'select', 'textarea', 'date', 'int', 'boolean', 'static', 'varchar', 'decimal');
    }

    /**
     * @param $attribute
     *
     * @return mixed|string
     */
    public function getLabel($attribute)
    {
        if ($attribute->getData('frontend_label')) {
            $label = str_replace("'", "", $attribute->getData('frontend_label'));
        } else {
            $label = str_replace("'", "", $attribute->getData('attribute_code'));
        }

        return trim($label);
    }

}