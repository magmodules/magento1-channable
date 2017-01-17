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

class Magmodules_Channable_Model_Common extends Mage_Core_Helper_Abstract
{

    /**
     * @param        $config
     * @param string $limit
     * @param int    $page
     * @param string $type
     *
     * @return mixed
     */
    public function getProducts($config, $limit = '', $page = 1, $type = '')
    {
        $storeId = $config['store_id'];
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setStore($storeId);
        $collection->addStoreFilter($storeId);
        $collection->addFinalPrice();
        $collection->addUrlRewrite();

        if (!empty($config['filter_enabled'])) {
            $filterType = $config['filter_type'];
            $categories = $config['filter_cat'];
            if ($filterType && $categories) {
                $table = Mage::getSingleton('core/resource')->getTableName('catalog_category_product');
                if ($filterType == 'include') {
                    $collection->getSelect()->join(array('cats' => $table), 'cats.product_id = e.entity_id');
                    $collection->getSelect()->where('cats.category_id in (' . $categories . ')');
                } else {
                    $collection->getSelect()->join(array('cats' => $table), 'cats.product_id = e.entity_id');
                    $collection->getSelect()->where('cats.category_id not in (' . $categories . ')');
                }
            }
        }

        $collection->addAttributeToFilter('status', 1);

        if (($limit) && ($type != 'count')) {
            $collection->setPage($page, $limit)->getCurPage();
            if ($collection->getLastPageNumber() < $page) {
                return array();
            }
        }

        if (empty($config['conf_enabled'])) {
            $collection->addAttributeToFilter('visibility', array('in' => array(2, 3, 4)));
        }

        if (!empty($config['filters'])) {
            $this->addFilters($config['filters'], $collection);
        }

        if (!empty($config['hide_no_stock'])) {
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
        }

        if ($type != 'count') {
            $attributes = $this->getDefaultAttributes();

            if (!empty($config['filter_exclude'])) {
                $attributes[] = $config['filter_exclude'];
            }

            foreach ($config['field'] as $field) {
                if (!empty($field['source'])) {
                    $attributes[] = $field['source'];
                }
            }

            if (!empty($config['delivery_att'])) {
                $attributes[] = $config['delivery_att'];
            }

            if (!empty($config['delivery_att_be'])) {
                $attributes[] = $config['delivery_att_be'];
            }

            if (!empty($config['media_attributes'])) {
                foreach ($config['media_attributes'] as $mediaAtt) {
                    $attributes[] = $mediaAtt;
                }
            }

            $customValues = '';
            if (isset($config['custom_name'])) {
                $customValues .= $config['custom_name'] . ' ';
            }

            if (isset($config['custom_description'])) {
                $customValues .= $config['custom_description'] . ' ';
            }

            if (isset($config['category_default'])) {
                $customValues .= $config['category_default'] . ' ';
            }

            preg_match_all("/{{([^}]*)}}/", $customValues, $foundAtts);
            if (!empty($foundAtts)) {
                foreach ($foundAtts[1] as $att) {
                    $attributes[] = $att;
                }
            }

            $collection->addAttributeToSelect(array_unique($attributes));

            if (!empty($config['filters'])) {
                $this->addFilters($config['filters'], $collection);
            }

            $collection->joinTable(
                'cataloginventory/stock_item', 'product_id=entity_id', array(
                    "qty"                       => "qty",
                    "is_in_stock"               => "is_in_stock",
                    "manage_stock"              => "manage_stock",
                    "use_config_manage_stock"   => "use_config_manage_stock",
                    "min_sale_qty"              => "min_sale_qty",
                    "qty_increments"            => "qty_increments",
                    "enable_qty_increments"     => "enable_qty_increments",
                    "use_config_qty_increments" => "use_config_qty_increments"
                )
            )->addAttributeToSelect(
                array(
                    'qty',
                    'is_in_stock',
                    'manage_stock',
                    'use_config_manage_stock',
                    'min_sale_qty',
                    'qty_increments',
                    'enable_qty_increments',
                    'use_config_qty_increments'
                )
            );

            $collection->getSelect()->group('e.entity_id');

            $products = $collection->load();
        } else {
            $products = $collection->getSize();
        }

        return $products;
    }

    /**
     * @param $filters
     * @param $collection
     */
    public function addFilters($filters, $collection)
    {
        foreach ($filters as $filter) {
            $attribute = $filter['attribute'];
            if ($filter['type'] == 'select') {
                $attribute = $filter['attribute'] . '_value';
            }

            $condition = $filter['condition'];
            $value = $filter['value'];

            if ($attribute == 'final_price') {
                $cType = array('eq' => '=', 'neq' => '!=', 'gt' => '>', 'gteq' => '>=', 'lt' => '<', 'lteg' => '<=');
                if (isset($cType[$condition])) {
                    $collection->getSelect()->where('price_index.final_price ' . $cType[$condition] . ' ' . $value);
                }

                continue;
            }

            switch ($condition) {
                case 'nin':
                    if (strpos($value, ',') !== false) {
                        $value = explode(',', $value);
                    }

                    $collection->addAttributeToFilter(
                        array(
                            array(
                                'attribute' => $attribute,
                                $condition  => $value
                            ),
                            array('attribute' => $attribute, 'null' => true)
                        )
                    );
                    break;
                case 'in';
                    if (strpos($value, ',') !== false) {
                        $value = explode(',', $value);
                    }

                    $collection->addAttributeToFilter($attribute, array($condition => $value));
                    break;
                case 'neq':
                    $collection->addAttributeToFilter(
                        array(
                            array('attribute' => $attribute, $condition => $value),
                            array('attribute' => $attribute, 'null' => true)
                        )
                    );
                    break;
                case 'empty':
                    $collection->addAttributeToFilter($attribute, array('null' => true));
                    break;
                case 'not-empty':
                    $collection->addAttributeToFilter($attribute, array('notnull' => true));
                    break;
                default:
                    $collection->addAttributeToFilter($attribute, array($condition => $value));
                    break;
            }
        }
    }

    /**
     * Araay of default Attributes
     *
     * @return array
     */
    public function getDefaultAttributes()
    {
        $attributes = array();
        $attributes[] = 'url_key';
        $attributes[] = 'url_path';
        $attributes[] = 'sku';
        $attributes[] = 'price';
        $attributes[] = 'final_price';
        $attributes[] = 'price_model';
        $attributes[] = 'price_type';
        $attributes[] = 'special_price';
        $attributes[] = 'special_from_date';
        $attributes[] = 'special_to_date';
        $attributes[] = 'type_id';
        $attributes[] = 'tax_class_id';
        $attributes[] = 'tax_percent';
        $attributes[] = 'weight';
        $attributes[] = 'visibility';
        $attributes[] = 'type_id';
        $attributes[] = 'image';
        $attributes[] = 'small_image';
        $attributes[] = 'thumbnail';
        $attributes[] = 'status';

        return $attributes;
    }

    /**
     * @param $products
     * @param $config
     *
     * @return array|bool
     */
    public function getParents($products, $config)
    {
        if (!empty($config['conf_enabled'])) {
            $ids = array();
            foreach ($products as $product) {
                if ($parentId = Mage::helper('channable')->getParentData($product, $config)) {
                    $ids[] = $parentId;
                }
            }

            if (empty($ids)) {
                return array();
            }

            $collection = Mage::getResourceModel('catalog/product_collection')
                ->setStore($config['store_id'])
                ->addStoreFilter($config['store_id'])
                ->addFinalPrice()
                ->addUrlRewrite()
                ->addAttributeToFilter('entity_id', array('in', $ids))
                ->addAttributeToSelect(array_unique($config['parent_att']));

            if (!empty($config['hide_no_stock'])) {
                Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
            }

            return $collection->load();
        }

        return false;
    }

    /**
     * @param $atts
     *
     * @return array
     */
    public function getParentAttributeSelection($atts)
    {
        $attributes = $this->getDefaultAttributes();
        $extraAttributes = explode(',', $atts);

        return array_merge($attributes, $extraAttributes);
    }
}
