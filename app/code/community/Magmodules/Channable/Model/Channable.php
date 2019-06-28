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

class Magmodules_Channable_Model_Channable extends Magmodules_Channable_Model_Common
{

    /**
     * @var Magmodules_Channable_Helper_Data
     */
    public $helper;
    /**
     * @var Mage_Tax_Helper_Data
     */
    public $taxHelper;

    /**
     * Magmodules_Channable_Model_Googleshopping constructor.
     */
    public function __construct()
    {
        $this->helper = Mage::helper('channable');
        $this->taxHelper = Mage::helper('tax');
    }

    /**
     * @param     $storeId
     * @param     $timeStart
     * @param int $page
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    public function generateFeed($storeId, $timeStart, $page = 1)
    {
        $this->setMemoryLimit($storeId);
        $this->cleanItemUpdates($storeId, $page);
        $config = $this->getFeedConfig($storeId);

        $productCollection = $this->getProducts($config);
        $totalCount = $this->getCollectionCountWithFilters($productCollection);

        if (($config['limit'] > 0) && empty($productId)) {
            $productCollection->getSelect()->limitPage($page, $config['limit']);
            $pages = ceil($totalCount / $config['limit']);
        } else {
            $pages = 1;
        }

        $products = $productCollection->load();
        $parentRelations = $this->helper->getParentsFromCollection($products, $config);
        $parents = $this->getParents($parentRelations, $config);
        $prices = $this->helper->getTypePrices($config, $parents);
        $parentAttributes = $this->helper->getConfigurableAttributesAsArray($parents, $config);

        if ($feed = $this->getFeedData($products, $parents, $config, $parentAttributes, $prices, $parentRelations)) {
            $returnFeed = array();
            $returnFeed['config'] = $this->getFeedHeader($config, $totalCount, $timeStart, count($feed), $page, $pages);
            $returnFeed['products'] = $feed;
            return $returnFeed;
        } else {
            $returnFeed = array();
            $returnFeed['config'] = $this->getFeedHeader($config, $totalCount, $timeStart, 0, $page, $pages);
            $returnFeed['products'] = $feed;
            return $returnFeed;
        }
    }

    /**
     * @param $storeId
     */
    protected function setMemoryLimit($storeId)
    {
        if ($this->helper->getConfigData('server/overwrite', $storeId)) {
            if ($memoryLimit = $this->helper->getConfigData('server/memory_limit', $storeId)) {
                ini_set('memory_limit', $memoryLimit);
            }

            if ($maxExecutionTime = $this->helper->getConfigData('server/max_execution_time', $storeId)) {
                ini_set('max_execution_time', $maxExecutionTime);
            }
        }
    }

    /**
     * @param $storeId
     * @param $page
     */
    protected function cleanItemUpdates($storeId, $page)
    {
        if (empty($page) || ($page == 1)) {
            if (Mage::helper('core')->isModuleEnabled('Magmodules_Channableapi')) {
                Mage::getModel('channableapi/items')->cleanItemStore($storeId);
            }
        }
    }

    /**
     * @param        $storeId
     * @param string $type
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    public function getFeedConfig($storeId, $type = 'xml')
    {
        $config = array();

        /** @var  Mage_Core_Model_Store $store */
        $store = Mage::getModel('core/store')->load($storeId);
        /** @var  Mage_Core_Model_Website $website */
        $website = Mage::getModel('core/website')->load($store->getWebsiteId());
        $websiteId = $website->getId();
        /** @var Mage_Eav_Model_Resource_Entity_Attribute $attribute */
        $attribute = Mage::getResourceModel('eav/entity_attribute');

        $config['store_id'] = $storeId;
        $config['website_id'] = $websiteId;
        $config['website_name'] = $this->helper->cleanData($website->getName(), 'striptags');
        $config['website_url'] = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $config['media_url'] = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $config['media_image_url'] = $config['media_url'] . 'catalog' . DS . 'product';
        $config['media_attributes'] = $this->helper->getMediaAttributes();
        $config['limit'] = $this->helper->getConfigData('connect/max_products', $storeId);
        $config['version'] = (string)Mage::getConfig()->getNode()->modules->Magmodules_Channable->version;
        $config['media_gallery_id'] = $attribute->getIdByCode('catalog_product', 'media_gallery');
        $config['filters'] = $this->helper->getSerializedConfigData('filter/advanced', $storeId);
        $config['product_url_suffix'] = $this->helper->getProductUrlSuffix($storeId);
        $config['filter_enabled'] = $this->helper->getConfigData('filter/category_enabled', $storeId);
        $config['filter_cat'] = $this->helper->getConfigData('filter/categories', $storeId);
        $config['filter_type'] = $this->helper->getConfigData('filter/category_type', $storeId);
        $config['filter_status'] = $this->helper->getConfigData('filter/visibility_inc', $storeId);
        $config['hide_no_stock'] = $this->helper->getConfigData('filter/stock', $storeId);
        $config['conf_enabled'] = $this->helper->getConfigData('data/conf_enabled', $storeId);
        $config['conf_fields'] = $this->helper->getConfigData('data/conf_fields', $storeId);
        $config['stock_bundle'] = $this->helper->getConfigData('data/stock_bundle', $storeId);
        $config['conf_switch_urls'] = $this->helper->getConfigData('data/conf_switch_urls', $storeId);
        $config['simple_price'] = $this->helper->getConfigData('data/simple_price', $storeId);
        $config['stock_manage'] = Mage::getStoreConfig('cataloginventory/item_options/manage_stock');
        $config['use_qty_increments'] = Mage::getStoreConfig('cataloginventory/item_options/enable_qty_increments');
        $config['qty_increments'] = Mage::getStoreConfig('cataloginventory/item_options/qty_increments');
        $config['backorders'] = Mage::getStoreConfig('cataloginventory/item_options/backorders');
        $config['delivery'] = $this->helper->getConfigData('data/delivery', $storeId);
        $config['delivery_be'] = $this->helper->getConfigData('data/delivery_be', $storeId);
        $config['delivery_att'] = $this->helper->getConfigData('data/delivery_att', $storeId);
        $config['delivery_att_be'] = $this->helper->getConfigData('data/delivery_att_be', $storeId);
        $config['delivery_in'] = $this->helper->getConfigData('data/delivery_in', $storeId);
        $config['delivery_in_be'] = $this->helper->getConfigData('data/delivery_in_be', $storeId);
        $config['delivery_out'] = $this->helper->getConfigData('data/delivery_out', $storeId);
        $config['delivery_out_be'] = $this->helper->getConfigData('data/delivery_out_be', $storeId);
        $config['images'] = $this->helper->getConfigData('data/images', $storeId);
        $config['default_image'] = $this->helper->getConfigData('data/default_image', $storeId);
        $config['weight'] = $this->helper->getConfigData('data/weight', $storeId);
        $config['weight_units'] = $this->helper->getConfigData('data/weight_units', $storeId);
        #$config['price_scope'] = Mage::getStoreConfig('catalog/price/scope');
        $config['price_add_tax'] = $this->helper->getConfigData('data/add_tax', $storeId);
        $config['price_add_tax_perc'] = $this->helper->getConfigData('data/tax_percentage', $storeId);
        $config['force_tax'] = $this->helper->getConfigData('data/force_tax', $storeId);
        $config['currency'] = $store->getDefaultCurrencyCode();
        $config['base_currency_code'] = $store->getBaseCurrencyCode();
        $config['use_currency'] = true;
        $config['markup'] = $this->helper->getPriceMarkup($config);
        $config['use_tax'] = $this->helper->getTaxUsage($config);
        $config['skip_validation'] = false;

        if ($type != 'API') {
            $config['category_exclude'] = 'channable_exclude';
            $config['root_category_id'] = $store->getRootCategoryId();
            $config['category_data'] = $this->helper->getCategoryData($config, $storeId);
        }

        $config['bypass_flat'] = $this->helper->getConfigData('server/bypass_flat', $storeId);

        if (Mage::helper('core')->isModuleEnabled('Magmodules_Channableapi')) {
            $config['item_updates'] = Mage::getStoreConfig('channable_api/item/enabled', $storeId);
        } else {
            $config['item_updates'] = '';
        }

        $config['shipping_prices'] = $this->helper->getSerializedConfigData('advanced/shipping_price', $storeId);
        $config['shipping_method'] = $this->helper->getConfigData('advanced/shipping_method', $storeId);
        $config['field'] = $this->getFeedAttributes($storeId, $type, $config);
        $config['parent_att'] = $this->getParentAttributeSelection($config['field']);

        return $config;
    }

    /**
     * @param int    $storeId
     * @param string $type
     * @param string $config
     *
     * @return array
     */
    public function getFeedAttributes($storeId = 0, $type = 'xml', $config = '')
    {
        $attributes = array();
        $attributes['id'] = array(
            'label'  => 'id',
            'source' => 'entity_id'
        );
        $attributes['name'] = array(
            'label'  => 'name',
            'source' => $this->helper->getConfigData('data/name', $storeId)
        );
        $attributes['price'] = array(
            'label'  => 'price',
            'source' => ''
        );
        $attributes['sku'] = array(
            'label'  => 'sku',
            'source' => $this->helper->getConfigData('data/sku', $storeId)
        );
        $attributes['ean'] = array(
            'label'  => 'ean',
            'source' => $this->helper->getConfigData('data/ean', $storeId)
        );
        $attributes['type'] = array(
            'label'  => 'type',
            'source' => 'type_id'
        );
        $attributes['status'] = array(
            'label'  => 'status',
            'source' => 'status',
            'parent' => 1
        );
        $attributes['visibility'] = array(
            'label'  => 'visibility',
            'source' => 'visibility'
        );
        $attributes['parent_id'] = array(
            'label'  => 'item_group_id',
            'source' => 'entity_id',
            'parent' => 1
        );
        $attributes['is_in_stock'] = array(
            'label'  => 'is_in_stock',
            'source' => 'is_in_stock'
        );
        if ($this->helper->getConfigData('data/stock', $storeId)) {
            $attributes['stock'] = array(
                'label'  => 'qty',
                'source' => 'qty',
                'action' => 'round'
            );
        }

        if ($type != 'API') {
            $attributes['description'] = array(
                'label'  => 'description',
                'source' => $this->helper->getConfigData('data/description', $storeId)
            );
            $attributes['product_url'] = array(
                'label'  => 'url',
                'source' => ''
            );
            $attributes['image_link'] = array(
                'label'  => 'image',
                'source' => $this->helper->getConfigData('data/default_image', $storeId)
            );
            $attributes['brand'] = array(
                'label'  => 'brand',
                'source' => $this->helper->getConfigData('data/brand', $storeId)
            );
            $attributes['size'] = array(
                'label'  => 'size',
                'source' => $this->helper->getConfigData('data/size', $storeId)
            );
            $attributes['color'] = array(
                'label'  => 'color',
                'source' => $this->helper->getConfigData('data/color', $storeId)
            );
            $attributes['material'] = array(
                'label'  => 'material',
                'source' => $this->helper->getConfigData('data/material', $storeId)
            );
            $attributes['gender'] = array(
                'label'  => 'gender',
                'source' => $this->helper->getConfigData('data/gender', $storeId)
            );
            $attributes['categories'] = array(
                'label'  => 'categories',
                'source' => '',
                'parent' => 1
            );
            $attributes['weight'] = array(
                'label'  => 'weight',
                'source' => ''
            );
            if ($this->helper->getConfigData('data/delivery', $storeId) == 'attribute') {
                $attributes['delivery'] = array(
                    'label'  => 'delivery',
                    'source' => $this->helper->getConfigData('data/delivery_att', $storeId)
                );
            }

            if ($this->helper->getConfigData('data/delivery_be', $storeId) == 'attribute') {
                $attributes['delivery_be'] = array(
                    'label'  => 'delivery_be',
                    'source' => $this->helper->getConfigData('data/delivery_att_be', $storeId)
                );
            }
        }

        if ($extraFields = $this->helper->getSerializedConfigData('advanced/extra', $storeId)) {
            $i = 1;
            foreach ($extraFields as $extraField) {
                $attributes['extra-' . $i] = array(
                    'label'  => $extraField['label'],
                    'source' => $extraField['attribute'],
                    'action' => ''
                );
                $i++;
            }
        }

        if ($type == 'selftest') {
            if($filters = $this->helper->getSerializedConfigData('filter/advanced', $storeId)) {
                $i = 1;
                foreach ($filters as $filter) {
                    $attributes['filter-' . $i] = array(
                        'label'  => $filter['attribute'],
                        'source' => $filter['attribute'],
                        'action' => ''
                    );
                    $i++;
                }
            }
        }

        if ($type != 'config') {
            $attributes = $this->helper->addAttributeData($attributes, $config);
        }

        return $attributes;
    }

    /**
     * @param Mage_Catalog_Model_Resource_Product_Collection $products
     * @param Mage_Catalog_Model_Resource_Product_Collection $parents
     * @param                                                $config
     * @param                                                $parentAttributes
     * @param                                                $prices
     * @param                                                $parentRelations
     *
     * @return array
     */
    public function getFeedData($products, $parents, $config, $parentAttributes, $prices, $parentRelations)
    {
        $feed = array();

        foreach ($products as $product) {
            $parent = null;
            $parents = $parentRelations[$product->getEntityId()];
            if (!empty($parents)) {
                foreach ($parents as $parentId) {
                    if ($parent = $parents->getItemById($parentId)) {

                        $productData = $this->helper->getProductDataRow($product, $config, $parent, $parentAttributes);
                        if ($productData) {
                            $productRow = array();
                            foreach ($productData as $key => $value) {
                                if (!is_array($value)) {
                                    $productRow[$key] = $value;
                                }
                            }

                            if ($extraData = $this->getExtraDataFields($productData, $config, $product, $prices)) {
                                $productRow = array_merge($productRow, $extraData);
                            }

                            $productRow = new Varien_Object($productRow);
                            $productRow = $productRow->getData();

                            $feed[] = $productRow;
                            if ($config['item_updates']) {
                                $this->processItemUpdates($productRow, $config['store_id']);
                            }

                            unset($productRow);
                        }

                    }
                }
            }


        }

        return $feed;
    }

    /**
     * @param                            $productData
     * @param                            $config
     * @param Mage_Catalog_Model_Product $product
     * @param                            $prices
     *
     * @return array
     */
    public function getExtraDataFields($productData, $config, $product, $prices)
    {
        $extra = array();
        if (!empty($productData['price'])) {
            if (!empty($productData['item_group_id'])) {
                $itemGroupId = $productData['item_group_id'];
            } else {
                $itemGroupId = '';
            }

            if ($priceData = $this->getPrices(
                $productData['price'],
                $prices,
                $product,
                $config,
                $itemGroupId
            )
            ) {
                $extra = array_merge($extra, $priceData);
            }
        }

        if ($stockData = $this->getStockData($productData, $config, $product)) {
            $extra = array_merge($extra, $stockData);
        }

        if ($shippingData = $this->getShipping($productData, $config, $product->getWeight(), $stockData)) {
            $extra = array_merge($extra, $shippingData);
        }

        if ($categoryData = $this->getCategoryData($productData)) {
            $extra = array_merge($extra, $categoryData);
        }

        if ($config['images'] == 'all') {
            if ($imageData = $this->getImages($productData, $config)) {
                $extra = array_merge($extra, $imageData);
            }
        }

        return $extra;
    }

    /**
     * @param                            $data
     * @param                            $confPrices
     * @param Mage_Catalog_Model_Product $product
     * @param                            $config
     * @param                            $itemGroupId
     *
     * @return array
     */
    public function getPrices($data, $confPrices, $product, $config, $itemGroupId)
    {
        $prices = array();
        $id = $product->getEntityId();
        $parentPriceIndex = $itemGroupId . '_' . $id;
        if ($itemGroupId && !empty($confPrices[$parentPriceIndex])) {
            $confPrice = $this->taxHelper->getPrice($product, $confPrices[$parentPriceIndex], true);
            $confPriceReg = $this->taxHelper->getPrice($product, $confPrices[$parentPriceIndex . '_reg'], true);
            if ($confPriceReg > $confPrice) {
                $prices['special_price'] = $this->helper->formatPrice($confPrice, $config);
                $prices['price'] = $this->helper->formatPrice($confPriceReg, $config);
            } else {
                $prices['price'] = $this->helper->formatPrice($confPrice, $config);
            }
        } else {
            $prices['price'] = $data['price'];
            if (isset($data['min_price'])) {
                $prices['min_price'] = $data['min_price'];
            }

            if (isset($data['max_price'])) {
                $prices['max_price'] = $data['max_price'];
            }

            if (isset($data['configured_price'])) {
                $prices['configured_price'] = $data['configured_price'];
            }

            $prices['special_price'] = '';
            $prices['special_price_from'] = '';
            $prices['special_price_to'] = '';
            if (isset($data['sales_price'])) {
                $prices['price'] = $data['regular_price'];
                $prices['special_price'] = $data['sales_price'];
                if (isset($data['sales_date_start'])) {
                    $prices['special_price_from'] = $data['sales_date_start'];
                }

                if (isset($data['sales_date_end'])) {
                    $prices['special_price_to'] = $data['sales_date_end'];
                }
            }
        }

        return $prices;
    }

    /**
     * @param                            $productData
     * @param                            $config
     * @param Mage_Catalog_Model_Product $product
     *
     * @return array
     */
    public function getStockData($productData, $config, $product)
    {
        $stockData = array();

        if (!isset($productData['qty'])) {
            $stockData['qty'] = (string)'0';
        }

        if (!isset($productData['manage_stock'])) {
            if ($product->getUseConfigManageStock()) {
                $stockData['manage_stock'] = (string)$config['stock_manage'];
            } else {
                $stockData['manage_stock'] = (string)$product->getManageStock();
            }
        }

        if (empty($productData['min_sale_qty'])) {
            if (!empty($product['min_sale_qty'])) {
                $stockData['min_sale_qty'] = (string)round($product['min_sale_qty']);
            } else {
                $stockData['min_sale_qty'] = '1';
            }
        }

        if (empty($productData['qty_increments'])) {
            if ($product->getUseEnableQtyIncrements()) {
                if (!empty($config['use_qty_increments'])) {
                    $stockData['qty_increments'] = (string)$config['qty_increments'];
                }
            } else {
                if ($product->getUseConfigQtyIncrements()) {
                    $stockData['qty_increments'] = (string)$config['qty_increments'];
                } else {
                    $stockData['qty_increments'] = round($product['qty_increments']);
                }
            }
        }

        if (empty($stockData['qty_increments'])) {
            $stockData['qty_increments'] = '1';
        }

        return $stockData;
    }

    /**
     * @param $data
     * @param $config
     * @param $weight
     * @param $stock
     *
     * @return array
     */
    public function getShipping($data, $config, $weight, $stock)
    {
        $shippingArray = array();

        if ($config['delivery'] == 'fixed') {
            if (!empty($stock['manage_stock'])) {
                if (!empty($stock['is_in_stock'])) {
                    if (!empty($config['delivery_in'])) {
                        $shippingArray['delivery'] = $config['delivery_in'];
                    }
                } else {
                    if (!empty($config['delivery_out'])) {
                        $shippingArray['delivery'] = $config['delivery_out'];
                    }
                }
            } else {
                if (!empty($config['delivery_in'])) {
                    $shippingArray['delivery'] = $config['delivery_in'];
                }
            }
        }

        if ($config['delivery_be'] == 'fixed') {
            if (!empty($stock['manage_stock'])) {
                if (!empty($stock['is_in_stock'])) {
                    if (!empty($config['delivery_in_be'])) {
                        $shippingArray['delivery_be'] = $config['delivery_in_be'];
                    }
                } else {
                    if (!empty($config['delivery_out'])) {
                        $shippingArray['delivery_be'] = $config['delivery_out_be'];
                    }
                }
            } else {
                if (!empty($config['delivery_in'])) {
                    $shippingArray['delivery_be'] = $config['delivery_in_be'];
                }
            }
        }

        if ($config['shipping_method'] == 'weight') {
            $calValue = $weight;
        } else {
            if (isset($data['price']['final_price_clean'])) {
                $calValue = $data['price']['final_price_clean'];
            } else {
                $calValue = '0.00';
            }
        }

        if (!empty($config['shipping_prices'])) {
            foreach ($config['shipping_prices'] as $shippingPrice) {
                if (($calValue >= $shippingPrice['price_from']) && ($calValue <= $shippingPrice['price_to'])) {
                    $shippingCost = $shippingPrice['cost'];
                    $shippingCost = number_format($shippingCost, 2, '.', '') . ' ' . $config['currency'];
                    if (empty($shippingPrice['country'])) {
                        $shippingArray['shipping'] = $shippingCost;
                    } else {
                        $label = 'shipping_' . strtolower($shippingPrice['country']);
                        $shippingArray[$label] = $shippingCost;
                        if (strtolower($shippingPrice['country']) == 'nl') {
                            $shippingArray['shipping'] = $shippingCost;
                        }
                    }
                }
            }
        }

        return $shippingArray;
    }

    /**
     * @param $productData
     *
     * @return array
     */
    public function getCategoryData($productData)
    {
        $category = array();
        $i = 0;
        if (!empty($productData['categories'])) {
            foreach ($productData['categories'] as $cat) {
                if (!empty($cat['path'])) {
                    if ($i == 0) {
                        $category['category'] = implode(' > ', $cat['path']);
                    }

                    $category['categories'][] = implode(' > ', $cat['path']);
                    $i++;
                }
            }
        }

        return $category;
    }

    /**
     * @param $productData
     * @param $config
     *
     * @return array
     */
    public function getImages($productData, $config)
    {
        $_images = array();

        if (!empty($config['default_image'])) {
            if (!empty($productData['image'][$config['default_image']])) {
                $_images['image_link'] = $productData['image'][$config['default_image']];
            }
        } else {
            if (!empty($productData['image']['base'])) {
                $_images['image_link'] = $productData['image']['base'];
            }
        }

        if (empty($_images['image_link'])) {
            if (!empty($productData['image_link'])) {
                $_images['image_link'] = $productData['image_link'];
            }
        }

        if (!empty($productData['image']['all'])) {
            $_additional = array();
            foreach ($productData['image']['all'] as $image) {
                if (empty($_images['image_link'])) {
                    $_images['image_link'] = $image;
                }

                if ($image != $_images['image_link']) {
                    $_additional[] = $image;
                }
            }

            if (!empty($_additional)) {
                $_images['additional_imagelinks'] = $_additional;
            }
        }

        return $_images;
    }

    /**
     * @param $productRow
     * @param $storeId
     */
    public function processItemUpdates($productRow, $storeId)
    {
        Mage::getModel('channableapi/items')->saveItemFeed($productRow, $storeId);
    }

    /**
     * @param     $config
     * @param     $count
     * @param     $timeStart
     * @param int $productCount
     * @param int $page
     * @param int $pages
     *
     * @return array
     */
    public function getFeedHeader($config, $count, $timeStart, $productCount = 0, $page = 1, $pages = 1)
    {
        $header = array();
        $header['system'] = 'Magento';
        $header['extension'] = 'Magmodules_Channable';
        $header['extension_version'] = $config['version'];
        $header['store'] = $config['website_name'];
        $header['url'] = $config['website_url'];
        $header['products_total'] = $count;
        $header['products_limit'] = $config['limit'];
        $header['products_output'] = $productCount;
        $header['products_pages'] = $pages;
        $header['current_page'] = ($page) ? $page : 1;
        $header['processing_time'] = number_format((microtime(true) - $timeStart), 4);

        if ($header['products_pages'] > $header['current_page']) {
            $header['next_page'] = 'true';
        } else {
            $header['next_page'] = 'false';
        }

        return $header;
    }
}
