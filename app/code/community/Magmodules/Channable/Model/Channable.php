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
     * Magmodules_Channable_Model_Googleshopping constructor.
     */
    public function __construct()
    {
        $this->helper = Mage::helper('channable');
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
            $productCollection->setPage($page, $config['limit'])->getCurPage();
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
     * @param        $storeId
     * @param string $type
     *
     * @return array
     */
    public function getFeedConfig($storeId, $type = 'xml')
    {

        $config = array();
        $store = Mage::getModel('core/store')->load($storeId);
        $websiteId = $store->getWebsiteId();
        $websiteName = Mage::getModel('core/website')->load($websiteId)->getName();
        $attribute = Mage::getResourceModel('eav/entity_attribute');

        $config['store_id'] = $storeId;
        $config['website_id'] = $websiteId;
        $config['website_name'] = $this->helper->cleanData($websiteName, 'striptags');
        $config['website_url'] = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $config['media_url'] = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $config['media_image_url'] = $config['media_url'] . 'catalog' . DS . 'product';
        $config['media_attributes'] = $this->helper->getMediaAttributes();
        $config['limit'] = Mage::getStoreConfig('channable/connect/max_products', $storeId);
        $config['version'] = (string)Mage::getConfig()->getNode()->modules->Magmodules_Channable->version;
        $config['media_gallery_id'] = $attribute->getIdByCode('catalog_product', 'media_gallery');
        $config['filters'] = @unserialize(Mage::getStoreConfig('channable/filter/advanced', $storeId));
        $config['product_url_suffix'] = $this->helper->getProductUrlSuffix($storeId);
        $config['filter_enabled'] = Mage::getStoreConfig('channable/filter/category_enabled', $storeId);
        $config['filter_cat'] = Mage::getStoreConfig('channable/filter/categories', $storeId);
        $config['filter_type'] = Mage::getStoreConfig('channable/filter/category_type', $storeId);
        $config['filter_status'] = Mage::getStoreConfig('channable/filter/visibility_inc', $storeId);
        $config['hide_no_stock'] = Mage::getStoreConfig('channable/filter/stock', $storeId);
        $config['conf_enabled'] = Mage::getStoreConfig('channable/data/conf_enabled', $storeId);
        $config['conf_fields'] = Mage::getStoreConfig('channable/data/conf_fields', $storeId);
        $config['stock_bundle'] = Mage::getStoreConfig('channable/data/stock_bundle', $storeId);
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
        $config['weight'] = Mage::getStoreConfig('channable/data/weight', $storeId);
        $config['weight_units'] = Mage::getStoreConfig('channable/data/weight_units', $storeId);
        $config['price_scope'] = Mage::getStoreConfig('catalog/price/scope');
        $config['price_add_tax'] = Mage::getStoreConfig('channable/data/add_tax', $storeId);
        $config['price_add_tax_perc'] = Mage::getStoreConfig('channable/data/tax_percentage', $storeId);
        $config['force_tax'] = Mage::getStoreConfig('channable/data/force_tax', $storeId);
        $config['currency'] = $store->getCurrentCurrencyCode();
        $config['base_currency_code'] = $store->getBaseCurrencyCode();
        $config['markup'] = $this->helper->getPriceMarkup($config);
        $config['use_tax'] = $this->helper->getTaxUsage($config);
        $config['skip_validation'] = false;

		if($type != 'API') {
    	    $config['category_exclude'] = 'channable_exclude';
	        $config['category_data'] = $this->helper->getCategoryData($config, $storeId);
		}

        $config['bypass_flat'] = Mage::getStoreConfig('channable/server/bypass_flat');

        if (Mage::helper('core')->isModuleEnabled('Magmodules_Channableapi')) {
            $config['item_updates'] = Mage::getStoreConfig('channable_api/item/enabled', $storeId);
        } else {
            $config['item_updates'] = '';
        }

        $config['shipping_prices'] = @unserialize(Mage::getStoreConfig('channable/advanced/shipping_price', $storeId));
        $config['shipping_method'] = Mage::getStoreConfig('channable/advanced/shipping_method', $storeId);
        $config['field'] = $this->getFeedAttributes($storeId, $type, $config);
        $config['parent_att'] = $this->getParentAttributeSelection($config['field']);

        return $config;
    }

    /**
     * @param string $config
     * @param int    $storeId
     *
     * @return mixed
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
            'source' => Mage::getStoreConfig('channable/data/name', $storeId)
        );
        $attributes['price'] = array(
            'label'  => 'price',
            'source' => ''
        );
        $attributes['sku'] = array(
            'label'  => 'sku',
            'source' => Mage::getStoreConfig('channable/data/sku', $storeId)
        );
     	$attributes['ean'] = array(
            'label'  => 'ean',
            'source' => Mage::getStoreConfig('channable/data/ean', $storeId)
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
        if (Mage::getStoreConfig('channable/data/stock', $storeId)) {
            $attributes['stock'] = array(
                'label'  => 'qty',
                'source' => 'qty',
                'action' => 'round'
            );
        }

		if($type != 'API') {
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
			$attributes['categories'] = array(
				'label'  => 'categories',
				'source' => '',
				'parent' => 1
			);
			$attributes['weight'] = array(
				'label'  => 'weight',
				'source' => ''
			);
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
        }        
     
        if ($extraFields = @unserialize(Mage::getStoreConfig('channable/advanced/extra', $storeId))) {
            $i = 1;
            foreach ($extraFields as $extraField) {
                $attributes['extra-'.$i] = array(
                    'label'  => $extraField['label'],
                    'source' => $extraField['attribute'],
                    'action' => ''
                );
                $i++;
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
            if (!empty($parentRelations[$product->getEntityId()])) {
                foreach ($parentRelations[$product->getEntityId()] as $parentId) {
                    if ($parent = $parents->getItemById($parentId)) {
                        continue;
                    }
                }
            }

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

                $feed[] = $productRow;
                if ($config['item_updates']) {
                    $this->processItemUpdates($productRow, $config['store_id']);
                }

                unset($productRow);
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
                $config['currency'],
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
     * @param                            $currency
     * @param                            $itemGroupId
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
            if(isset($data['min_price'])) {
	            $prices['min_price'] = $data['min_price'];
    		}
            if(isset($data['max_price'])) {
	            $prices['max_price'] = $data['max_price'];
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
