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

class Magmodules_Channable_Model_Channable extends Magmodules_Channable_Model_Common
{

    /**
     * @param        $storeId
     * @param string $limit
     * @param        $timeStart
     * @param int    $page
     *
     * @return array|bool
     */
    public function generateFeed($storeId, $limit = '', $timeStart, $page = 1)
    {
        $this->setMemoryLimit($storeId);
        $config = $this->getFeedConfig($storeId);
        $this->cleanItemUpdates($storeId, $page);
        $products = $this->getProducts($config, $limit, $page);
        $parents = $this->getParents($products, $config);
        $prices = Mage::helper('channable')->getTypePrices($config, $parents);
        $parentAttributes = Mage::helper('channable')->getConfigurableAttributesAsArray($parents, $config);
        if ($feed = $this->getFeedData($products, $parents, $config, $parentAttributes, $timeStart, $prices, $page)) {
            return $feed;
        }

        return false;
    }

    /**
     * @param $storeId
     */
    protected function setMemoryLimit($storeId)
    {
        if (Mage::getStoreConfig('channable/server/overwrite', $storeId)) {
            if ($memoryLimit = Mage::getStoreConfig('channable/server/memory_limit', $storeId)) {
                ini_set('memory_limit', $memoryLimit);
            }

            if ($maxExecutionTime = Mage::getStoreConfig('channable/server/max_execution_time', $storeId)) {
                ini_set('max_execution_time', $maxExecutionTime);
            }
        }
    }

    /**
     * @param $storeId
     *
     * @return array
     */
    public function getFeedConfig($storeId)
    {

        $config = array();
        $feed = Mage::helper('channable');
        $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();

        // DEFAULTS
        $config['store_id'] = $storeId;
        $config['website_name'] = $feed->cleanData(
            Mage::getModel('core/website')->load($websiteId)->getName(),
            'striptags'
        );
        $config['website_url'] = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $config['media_url'] = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $config['media_image_url'] = $config['media_url'] . 'catalog' . DS . 'product';
        $config['media_attributes'] = $feed->getMediaAttributes();
        $config['limit'] = Mage::getStoreConfig('channable/connect/max_products', $storeId);
        $config['version'] = (string) Mage::getConfig()->getNode()->modules->Magmodules_Channable->version;
        $config['media_gallery_id'] = Mage::getResourceModel('eav/entity_attribute')->getIdByCode(
            'catalog_product',
            'media_gallery'
        );
        $config['filters'] = @unserialize(Mage::getStoreConfig('channable/filter/advanced', $storeId));
        $config['product_url_suffix'] = $feed->getProductUrlSuffix($storeId);
        $config['filter_enabled'] = Mage::getStoreConfig('channable/filter/category_enabled', $storeId);
        $config['filter_cat'] = Mage::getStoreConfig('channable/filter/categories', $storeId);
        $config['filter_type'] = Mage::getStoreConfig('channable/filter/category_type', $storeId);
        $config['filter_status'] = Mage::getStoreConfig('channable/filter/visibility_inc', $storeId);
        $config['hide_no_stock'] = Mage::getStoreConfig('channable/filter/stock', $storeId);
        $config['conf_enabled'] = Mage::getStoreConfig('channable/data/conf_enabled', $storeId);
        $config['conf_fields'] = Mage::getStoreConfig('channable/data/conf_fields', $storeId);
        $config['parent_att'] = $this->getParentAttributeSelection($config['conf_fields']);
        $config['conf_switch_urls'] = Mage::getStoreConfig('channable/data/conf_switch_urls', $storeId);
        $config['simple_price'] = Mage::getStoreConfig('channable/data/simple_price', $storeId);
        $config['stock_manage'] = Mage::getStoreConfig('cataloginventory/item_options/manage_stock');
        $config['use_qty_increments'] = Mage::getStoreConfig('cataloginventory/item_options/enable_qty_increments');
        $config['qty_increments'] = Mage::getStoreConfig('cataloginventory/item_options/qty_increments');
        $config['delivery'] = Mage::getStoreConfig('channable/data/delivery', $storeId);
        $config['delivery_be'] = Mage::getStoreConfig('channable/data/delivery_be', $storeId);
        $config['delivery_att'] = Mage::getStoreConfig('channable/data/delivery_att', $storeId);
        $config['delivery_att_be'] = Mage::getStoreConfig('channable/data/delivery_att_be', $storeId);
        $config['delivery_in'] = Mage::getStoreConfig('channable/data/delivery_in', $storeId);
        $config['delivery_in_be'] = Mage::getStoreConfig('channable/data/delivery_in_be', $storeId);
        $config['delivery_out'] = Mage::getStoreConfig('channable/data/delivery_out', $storeId);
        $config['delivery_out_be'] = Mage::getStoreConfig('channable/data/delivery_out_be', $storeId);
        $config['images'] = Mage::getStoreConfig('channable/data/images', $storeId);
        $config['default_image'] = Mage::getStoreConfig('channable/data/default_image', $storeId);
        $config['skip_validation'] = false;
        $config['weight'] = Mage::getStoreConfig('channable/data/weight', $storeId);
        $config['weight_units'] = Mage::getStoreConfig('channable/data/weight_units', $storeId);
        $config['price_scope'] = Mage::getStoreConfig('catalog/price/scope');
        $config['price_add_tax'] = Mage::getStoreConfig('channable/data/add_tax', $storeId);
        $config['price_add_tax_perc'] = Mage::getStoreConfig('channable/data/tax_percentage', $storeId);
        $config['force_tax'] = Mage::getStoreConfig('channable/data/force_tax', $storeId);
        $config['currency'] = Mage::app()->getStore($storeId)->getCurrentCurrencyCode();
        $config['base_currency_code'] = Mage::app()->getStore($storeId)->getBaseCurrencyCode();
        $config['markup'] = Mage::helper('channable')->getPriceMarkup($config);
        $config['use_tax'] = Mage::helper('channable')->getTaxUsage($config);

        if (Mage::helper('core')->isModuleEnabled('Magmodules_Channableapi')) {
            $config['item_updates'] = Mage::getStoreConfig('channable_api/item/enabled', $storeId);
        } else {
            $config['item_updates'] = '';
        }

        $config['shipping_prices'] = @unserialize(Mage::getStoreConfig('channable/advanced/shipping_price', $storeId));
        $config['shipping_method'] = Mage::getStoreConfig('channable/advanced/shipping_method', $storeId);
        $config['field'] = $this->getFeedAttributes($config, $storeId);
        $config['category_exclude'] = 'channable_exclude';
        $config['category_data'] = $feed->getCategoryData($config, $storeId);

        return $config;
    }

    /**
     * @param string $config
     * @param int    $storeId
     *
     * @return mixed
     */
    public function getFeedAttributes($config = '', $storeId = 0)
    {
        $attributes = array();
        $attributes['id'] = array(
            'label'  => 'id',
            'source' => 'entity_id'
        );
        $attributes['name'] = array(
            'label'  => 'name',
            'source' => Mage::getStoreConfig('channable/data/name', $storeId)
        );
        $attributes['description'] = array(
            'label'  => 'description',
            'source' => Mage::getStoreConfig('channable/data/description', $storeId)
        );
        $attributes['product_url'] = array(
            'label'  => 'url',
            'source' => ''
        );
        $attributes['image_link'] = array(
            'label'  => 'image',
            'source' => Mage::getStoreConfig('channable/data/default_image', $storeId)
        );
        $attributes['price'] = array(
            'label'  => 'price',
            'source' => ''
        );
        $attributes['sku'] = array(
            'label'  => 'sku',
            'source' => Mage::getStoreConfig('channable/data/sku', $storeId)
        );
        $attributes['brand'] = array(
            'label'  => 'brand',
            'source' => Mage::getStoreConfig('channable/data/brand', $storeId)
        );
        $attributes['size'] = array(
            'label'  => 'size',
            'source' => Mage::getStoreConfig('channable/data/size', $storeId)
        );
        $attributes['color'] = array(
            'label'  => 'color',
            'source' => Mage::getStoreConfig('channable/data/color', $storeId)
        );
        $attributes['material'] = array(
            'label'  => 'material',
            'source' => Mage::getStoreConfig('channable/data/material', $storeId)
        );
        $attributes['gender'] = array(
            'label'  => 'gender',
            'source' => Mage::getStoreConfig('channable/data/gender', $storeId)
        );
        $attributes['ean'] = array(
            'label'  => 'ean',
            'source' => Mage::getStoreConfig('channable/data/ean', $storeId)
        );
        $attributes['categories'] = array(
            'label'  => 'categories',
            'source' => '',
            'parent' => 1
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
        $attributes['weight'] = array(
            'label'  => 'weight',
            'source' => ''
        );
        $attributes['is_in_stock'] = array(
            'label'  => 'is_in_stock',
            'source' => 'is_in_stock'
        );

        if (Mage::getStoreConfig('channable/data/stock', $storeId)) {
            $attributes['stock'] = array(
                'label'  => 'qty',
                'source' => 'qty',
                'action' => 'round'
            );
        }

        if (Mage::getStoreConfig('channable/data/delivery', $storeId) == 'attribute') {
            $attributes['delivery'] = array(
                'label'  => 'delivery',
                'source' => Mage::getStoreConfig('channable/data/delivery_att', $storeId)
            );
        }

        if (Mage::getStoreConfig('channable/data/delivery_be', $storeId) == 'attribute') {
            $attributes['delivery_be'] = array(
                'label'  => 'delivery_be',
                'source' => Mage::getStoreConfig('channable/data/delivery_att_be', $storeId)
            );
        }

        if ($extraFields = @unserialize(Mage::getStoreConfig('channable/advanced/extra', $storeId))) {
            foreach ($extraFields as $extraField) {
                $attributes[$extraField['attribute']] = array(
                    'label'  => $extraField['label'],
                    'source' => $extraField['attribute'],
                    'action' => ''
                );
            }
        }

        return Mage::helper('channable')->addAttributeData($attributes, $config);
    }

    /**
     * @param $storeId
     * @param $page
     */
    protected function cleanItemUpdates($storeId, $page)
    {
        if (empty($page)) {
            if (Mage::helper('core')->isModuleEnabled('Magmodules_Channableapi')) {
                Mage::getModel('channableapi/items')->cleanItemStore($storeId);
            }
        }
    }

    /**
     * @param $products
     * @param $parents
     * @param $config
     * @param $parentAttributes
     * @param $timeStart
     * @param $prices
     * @param $page
     *
     * @return array
     */
    public function getFeedData($products, $parents, $config, $parentAttributes, $timeStart, $prices, $page)
    {
        $count = $this->getProducts($config, '', '', 'count');
        foreach ($products as $product) {
            if ($parentId = Mage::helper('channable')->getParentData($product, $config)) {
                $parent = $parents->getItemById($parentId);
            } else {
                $parent = '';
            }

            $productData = Mage::helper('channable')->getProductDataRow($product, $config, $parent, $parentAttributes);

            if ($productData) {
                foreach ($productData as $key => $value) {
                    if (!is_array($value)) {
                        $productRow[$key] = $value;
                    }
                }

                if ($extraData = $this->getExtraDataFields($productData, $config, $product, $prices)) {
                    $productRow = array_merge($productRow, $extraData);
                }

                $feed['products'][] = $productRow;
                if ($config['item_updates']) {
                    $this->processItemUpdates($productRow, $config['store_id']);
                }

                unset($productRow);
            }
        }

        if (!empty($feed)) {
            $returnFeed = array();
            $returnFeed['config'] = $this->getFeedHeader($config, $count, $timeStart, count($feed['products']), $page);
            $returnFeed['products'] = $feed['products'];
            return $returnFeed;
        } else {
            $returnFeed = array();
            $returnFeed['config'] = $this->getFeedHeader($config, $count, $timeStart, '', $page);
            return $returnFeed;
        }
    }

    /**
     * @param $productData
     * @param $config
     * @param $product
     * @param $prices
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
                $productData['price'], $prices, $product, $config['currency'],
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
     * @param $data
     * @param $confPrices
     * @param $product
     * @param $currency
     * @param $itemGroupId
     *
     * @return array
     */
    public function getPrices($data, $confPrices, $product, $currency, $itemGroupId)
    {
        $prices = array();
        $id = $product->getEntityId();
        $parentPriceIndex = $itemGroupId . '_' . $id;
        if ($itemGroupId && !empty($confPrices[$parentPriceIndex])) {
            $confPrice = Mage::helper('tax')->getPrice($product, $confPrices[$parentPriceIndex], true);
            $confPriceReg = Mage::helper('tax')->getPrice($product, $confPrices[$parentPriceIndex . '_reg'], true);
            if ($confPriceReg > $confPrice) {
                $prices['special_price'] = number_format($confPrice, 2, '.', '') . ' ' . $currency;
                $prices['price'] = number_format($confPriceReg, 2, '.', '') . ' ' . $currency;
            } else {
                $prices['price'] = number_format($confPrice, 2, '.', '') . ' ' . $currency;
            }
        } else {
            $prices['price'] = $data['price'];
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
     * @param $productData
     * @param $config
     * @param $product
     *
     * @return array
     */
    public function getStockData($productData, $config, $product)
    {
        $stockData = array();
        $stockData['is_in_stock'] = $productData['is_in_stock'];

        if (!empty($productData['qty'])) {
            $stockData['qty'] = $productData['qty'];
        } else {
            $stockData['qty'] = (string) '0';
        }

        if ($product->getUseConfigManageStock()) {
            $stockData['manage_stock'] = (string) $config['stock_manage'];
        } else {
            $stockData['manage_stock'] = (string) $product->getManageStock();
        }

        if (!empty($product['min_sale_qty'])) {
            $stockData['min_sale_qty'] = (string) round($product['min_sale_qty']);
        } else {
            $stockData['min_sale_qty'] = '1';
        }

        if ($product->getUseEnableQtyIncrements()) {
            if (!empty($config['use_qty_increments'])) {
                $stockData['qty_increments'] = (string) $config['qty_increments'];
            }
        } else {
            if ($product->getUseConfigQtyIncrements()) {
                $stockData['qty_increments'] = (string) $config['qty_increments'];
            } else {
                $stockData['qty_increments'] = round($product['qty_increments']);
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
     *
     * @return array
     */
    public function getFeedHeader($config, $count, $timeStart, $productCount = 0, $page = 1)
    {
        $pages = (($config['limit']) && ($count > $config['limit'])) ? ceil($count / $config['limit']) : 1;
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
