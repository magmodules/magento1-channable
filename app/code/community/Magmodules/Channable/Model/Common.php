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

class Magmodules_Channable_Model_Common extends Mage_Core_Helper_Abstract
{

    /**
     * @param        $config
     * @param array  $productIds
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     * @throws Mage_Core_Exception
     */
    public function getProducts($config, $productIds = array())
    {
        $storeId = $config['store_id'];
        $websiteId = $config['website_id'];

        if (!empty($config['bypass_flat'])) {
            $collection = Mage::getModel('channable/resource_product_collection');
        } else {
            $collection = Mage::getResourceModel('catalog/product_collection');
        }

        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection->setStore($storeId)
            ->addStoreFilter($storeId)
            ->addUrlRewrite()
            ->addAttributeToFilter('status', 1);

        if (!empty($productIds)) {
            $collection->addAttributeToFilter('entity_id', array('in' => $productIds));
        }

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

        if (empty($config['conf_enabled'])) {
            $collection->addAttributeToFilter('visibility', array('in' => array(2, 3, 4)));
        }

        if (!empty($config['filters'])) {
            $this->addFilters($config['filters'], $collection);
        }

        if (!empty($config['hide_no_stock'])) {
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
        }

        $attributes = $this->getAttributes($config['field']);
        $collection->addAttributeToSelect($attributes);

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
		
		$this->joinPriceIndexLeft($collection, $websiteId);
        $collection->getSelect()->group('e.entity_id');

        if (!empty($config['filters'])) {
            $this->addFilters($config['filters'], $collection);
        }

        return $collection;
    }

    /**
     * @param $collection
     * @param $websiteId
     */
    public function joinPriceIndexLeft($collection, $websiteId)
	{
		$resource = Mage::getResourceSingleton('core/resource');
        $tableName = array('price_index' => $resource->getTable('catalog/product_index_price'));
	    $joinCond = join(' AND ', array(
            'price_index.entity_id = e.entity_id',
            'price_index.website_id = ' . $websiteId,
            'price_index.customer_group_id = 0'
        ));
        $colls = array('final_price', 'min_price', 'max_price');
        $collection->getSelect()->joinLeft($tableName, $joinCond, $colls);
	}
	
    /**
     * @param $selectedAttrs
     *
     * @return array
     */
    public function getAttributes($selectedAttrs)
    {
        $attributes = $this->getDefaultAttributes();
        foreach ($selectedAttrs as $selectedAtt) {
            if (!empty($selectedAtt['source'])) {
                $attributes[] = $selectedAtt['source'];
            }

            if (!empty($selectedAtt['multi']) && is_array($selectedAtt['multi'])) {
                foreach ($selectedAtt['multi'] as $attribute) {
                    $attributes[] = $attribute['source'];
                }
            }

            if (!empty($selectedAtt['main'])) {
                $attributes[] = $selectedAtt['main'];
            }
        }

        return array_unique($attributes);
    }

    /**
     * Array of default Attributes
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
     * @param                                                $filters
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     * @param                                                $type
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function addFilters($filters, $collection, $type = 'simple')
    {
        $cType = array(
            'eq'   => '=',
            'neq'  => '!=',
            'gt'   => '>',
            'gteq' => '>=',
            'lt'   => '<',
            'lteg' => '<='
        );

        foreach ($filters as $filter) {
            $attribute = $filter['attribute'];
            $condition = $filter['condition'];
            $value = $filter['value'];
            $productFilterType = $filter['product_type'];
            $filterExpr = array();

            if ($type == 'simple' && $productFilterType == 'parent') {
                continue;
            }

            if ($type == 'parent' && $productFilterType == 'simple') {
                continue;
            }

            $attributeModel = Mage::getSingleton('eav/config')
                ->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute);
            if (!$frontendInput = $attributeModel->getFrontendInput()) {
                continue;
            }

            if ($frontendInput == 'select' || $frontendInput == 'multiselect') {
                $options = $attributeModel->getSource()->getAllOptions();
                if (strpos($value, ',') !== false) {
                    $values = array();
                    $value = explode(',', $value);
                    foreach ($value as $v) {
                        $valueId = array_search(trim($v), array_column($options, 'label'));
                        if ($valueId) {
                            $values[] = $options[$valueId]['value'];
                        }
                    }

                    $value = implode(',', $values);
                } else {
                    $valueId = array_search($value, array_column($options, 'label'));
                    if ($valueId) {
                        $value = $options[$valueId]['value'];
                    }
                }
            }

            if ($attribute == 'final_price') {
                if (isset($cType[$condition])) {
                    $collection->getSelect()->where('price_index.final_price ' . $cType[$condition] . ' ' . $value);
                }

                continue;
            }

            if ($attribute == 'min_sale_qty') {
                if (isset($cType[$condition])) {
                    $collection->getSelect()->where('cataloginventory_stock_item.min_sale_qty ' . $cType[$condition] . ' ' . $value);
                }

                continue;
            }

            switch ($condition) {
                case 'nin':
                    if (strpos($value, ',') !== false) {
                        $value = explode(',', $value);
                    }

                    $filterExpr[] = array('attribute' => $attribute, $condition => $value);
                    $filterExpr[] = array('attribute' => $attribute, 'null' => true);
                    break;
                case 'in';
                    if (strpos($value, ',') !== false) {
                        $value = explode(',', $value);
                    }

                    $filterExpr[] = array('attribute' => $attribute, $condition => $value);
                    break;
                case 'neq':
                    $filterExpr[] = array('attribute' => $attribute, $condition => $value);
                    $filterExpr[] = array('attribute' => $attribute, 'null' => true);
                    break;
                case 'empty':
                    $filterExpr[] = array('attribute' => $attribute, 'null' => true);
                    break;
                case 'not-empty':
                    $filterExpr[] = array('attribute' => $attribute, 'notnull' => true);
                    break;
                case 'gt':
                case 'gteq':
                case 'lt':
                case 'lteq':
                    if (is_numeric($value)) {
                        $filterExpr[] = array('attribute' => $attribute, $condition => $value);
                    }
                    break;
                default:
                    $filterExpr[] = array('attribute' => $attribute, $condition => $value);
                    break;
            }

            if (!empty($filterExpr)) {
                if ($productFilterType == 'parent') {
                    $filterExpr[] = array('attribute' => 'type_id', 'eq' => 'simple');
                    /** @noinspection PhpParamsInspection */
                    $collection->addAttributeToFilter($filterExpr, '', 'left');
                } elseif ($productFilterType == 'simple') {
                    $filterExpr[] = array('attribute' => 'type_id', 'neq' => 'simple');
                    /** @noinspection PhpParamsInspection */
                    $collection->addAttributeToFilter($filterExpr, '', 'left');
                } else {
                    /** @noinspection PhpParamsInspection */
                    $collection->addAttributeToFilter($filterExpr);
                }
            }
        }

        return $collection;
    }

    /**
     * @param $atts
     *
     * @return array
     */
    public function getParentAttributeSelection($atts)
    {
        $attributes = $this->getDefaultAttributes();
        foreach ($atts as $attribute) {
            if (!empty($attribute['parent'])) {
                if (!empty($attribute['source'])) {
                    if ($attribute['source'] != 'entity_id') {
                        $attributes[] = $attribute['source'];
                    }
                }
            }
        }

        return $attributes;
    }
    /**
     * @param array $parentRelations
     * @param array $config
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getParents($parentRelations, $config)
    {
        if (!empty($config['conf_enabled']) && !empty($parentRelations)) {
            if (!empty($config['bypass_flat'])) {
                $collection = Mage::getModel('channable/resource_product_collection');
            } else {
                $collection = Mage::getResourceModel('catalog/product_collection');
            }

            /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
            $collection->setStore($config['store_id'])
                ->addStoreFilter($config['store_id'])
                ->addUrlRewrite()
                ->addAttributeToFilter('entity_id', array('in' => array_values($parentRelations)))
                ->addAttributeToSelect(array_unique($config['parent_att']))
                ->addAttributeToFilter('status', 1);

            if (!empty($config['hide_no_stock'])) {
                Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
            }

			$this->joinPriceIndexLeft($collection, $config['website_id']);
            $collection->getSelect()->group('e.entity_id');

            if (!empty($config['filters'])) {
                $collection = $this->addFilters($config['filters'], $collection, 'parent');
            }

            return $collection->load();
        }
    }

    /**
     * Direct Database Query to get total records of collection with filters.
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $productCollection
     *
     * @return int
     */
    public function getCollectionCountWithFilters($productCollection)
    {
        $selectCountSql = $productCollection->getSelectCountSql();
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $count = $connection->fetchOne($selectCountSql);
        return $count;
    }
}
