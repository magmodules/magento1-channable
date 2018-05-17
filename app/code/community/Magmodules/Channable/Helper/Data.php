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

class Magmodules_Channable_Helper_Data extends Mage_Core_Helper_Abstract
{

    const LOG_FILENAME = 'channable.log';
    const FORCE_LOG = true;

    /**
     * @param $path
     *
     * @return array
     */
    public function getStoreIds($path)
    {
        $storeIds = array();
        $stores = Mage::getModel('core/store')->getCollection();
        foreach ($stores as $store) {
            if (Mage::getStoreConfig($path, $store->getId())) {
                $storeIds[] = $store->getId();
            }
        }

        return $storeIds;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param                            $config
     * @param Mage_Catalog_Model_Product $parent
     * @param                            $parentAttributes
     *
     * @return array|bool
     */
    public function getProductDataRow($product, $config, $parent, $parentAttributes)
    {

        $fields = $config['field'];
        $data = array();

        if (!$this->validateParent($parent, $config, $product)) {
            $parent = '';
        }

        if (!$this->validateProduct($product, $config, $parent)) {
            return false;
        }

        foreach ($fields as $key => $field) {
            $rows = $this->getAttributeValue($key, $product, $config, $field['action'], $parent, $parentAttributes);
            if (is_array($rows)) {
                $data = array_merge($data, $rows);
            }
        }

        if (empty($config['skip_validation'])) {
            if (!empty($data[$fields['price']['label']])) {
                return $data;
            }
        } else {
            return $data;
        }

        return false;
    }

    /**
     * @param Mage_Catalog_Model_Product $parent
     * @param                            $config
     * @param Mage_Catalog_Model_Product $product
     *
     * @return bool
     */
    public function validateParent($parent, $config, $product)
    {
        return $this->validateProduct($product, $config, $parent);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param                            $config
     * @param Mage_Catalog_Model_Product $parent
     *
     * @return bool
     */
    public function validateProduct($product, $config, $parent)
    {
        if (empty($config['skip_validation'])) {
            if ($product['visibility'] == 1) {
                if (empty($parent)) {
                    return false;
                }

                if ($parent['status'] != 1) {
                    return false;
                }
            }

            if (!empty($config['filter_exclude'])) {
                if ($product[$config['filter_exclude']] == 1) {
                    return false;
                }
            }

            if (!empty($config['hide_no_stock'])) {
                if ($product->getUseConfigManageStock()) {
                    $manageStock = $config['stock_manage'];
                } else {
                    $manageStock = $product->getManageStock();
                }

                if ($manageStock) {
                    if (!$product['is_in_stock']) {
                        return false;
                    }
                }
            }

            if (!empty($config['conf_exclude_parent'])) {
                if ($product->getTypeId() == 'configurable') {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param                                   $field
     * @param        Mage_Catalog_Model_Product $product
     * @param                                   $config
     * @param string                            $actions
     * @param        Mage_Catalog_Model_Product $parent
     * @param                                   $parentAttributes
     *
     * @return mixed
     */
    public function getAttributeValue($field, $product, $config, $actions = '', $parent, $parentAttributes)
    {
        $dataRow = array();
        $data = $config['field'][$field];
        $productData = $product;

        if (!empty($parent)) {
            if (!empty($data['parent'])) {
                $productData = $parent;
            }
        }

        if (!empty($data['source']) && ($data['source'] == 'attribute_set_name')) {
            $config['field']['attribute_set_name'] = $config['field'][$field];
            $field = 'attribute_set_name';
        }

        if (!empty($data['source']) && ($data['source'] == 'backorders')) {
            $config['field']['backorders'] = $config['field'][$field];
            $field = 'backorders';
        }

        switch ($field) {
            case 'product_url':
                $value = $this->getProductUrl($product, $config, $parent, $parentAttributes);
                break;
            case 'image_link':
                $value = $this->getProductImage($productData, $config);
                break;
            case 'condition':
                $value = $this->getProductCondition($productData, $config);
                break;
            case 'availability':
                $value = $this->getProductAvailability($productData, $config);
                break;
            case 'weight':
                $value = $this->getProductWeight($productData, $config);
                break;
            case 'price':
                $value = $this->getProductPrice($productData, $config);
                break;
            case 'bundle':
                $value = $this->getProductBundle($productData);
                break;
            case 'is_in_stock':
                $value = $this->getIsInStock($productData, $config);
                break;
            case 'parent_id':
                $value = $this->getProductData($parent, $data);
                break;
            case 'attribute_set_name':
                $value = $this->getAttributeSetName($productData);
                break;
            case 'stock':
                $value = $this->getStock($productData, $config['stock_bundle']);
                break;
            case 'backorders':
                $value = $this->getBackordersEnabled($productData, $config);
                break;
            case 'categories':
                $value = $this->getProductCategories($productData, $config);
                break;
            case 'category_ids':
                $value = $this->getProductCategoryIds($productData);
                break;
            default:
                if (!empty($data['source'])) {
                    $value = $this->getProductData($productData, $data, $config);
                } else {
                    $value = '';
                }
                break;
        }

        if ($config['field'][$field]['type'] == 'media_image') {
            if ($field != 'image_link') {
                if (!empty($value)) {
                    if ($value != 'no_selection') {
                        $value = $config['media_url'] . 'catalog/product' . $value;
                    } else {
                        $value = '';
                    }
                }
            }
        }

        if ((isset($actions)) && (!empty($value)) && !is_array($value)) {
            $value = $this->cleanData($value, $actions);
        }

        if ((is_array($value) && ($field == 'image_link'))) {
            $i = 1;
            foreach ($value as $key => $val) {
                $dataRow[$key] = $val;
                $i++;
            }

            return $dataRow;
        }

        $dataRow[$data['label']] = $value;
        return $dataRow;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param                            $config
     * @param Mage_Catalog_Model_Product $parent
     * @param                            $parentAttributes
     *
     * @return mixed|string
     */
    public function getProductUrl($product, $config, $parent, $parentAttributes)
    {
        $url = '';
        if (!empty($parent)) {
            if ($parent->getRequestPath()) {
                $url = Mage::helper('core')->escapeHtml(trim($config['website_url'] . $parent->getRequestPath()));
            }

            if (empty($url)) {
                if ($parent->getUrlKey()) {
                    $url = Mage::helper('core')->escapeHtml(trim($config['website_url'] . $parent->getUrlKey()));
                }
            }
        } else {
            if ($product->getRequestPath()) {
                $url = Mage::helper('core')->escapeHtml(trim($config['website_url'] . $product->getRequestPath()));
            }

            if (empty($url)) {
                if ($product->getUrlKey()) {
                    $url = Mage::helper('core')->escapeHtml(trim($config['website_url'] . $product->getUrlKey()));
                }
            }
        }

        if (!empty($config['product_url_suffix'])) {
            if (strpos($url, $config['product_url_suffix']) === false) {
                $url = $url . $config['product_url_suffix'];
            }
        }

        if (!empty($config['url_suffix'])) {
            $url = $url . '?' . $config['url_suffix'];
        }

        if (!empty($parent) && !empty($config['conf_switch_urls'])) {
            if ($parent->getTypeId() == 'configurable') {
                $productAttributeOptions = $parentAttributes[$parent->getEntityId()];
                $urlExtra = '';
                foreach ($productAttributeOptions as $productAttribute) {
                    if ($id = Mage::getResourceModel('catalog/product')->getAttributeRawValue(
                        $product->getId(),
                        $productAttribute['attribute_code'], $config['store_id']
                    )
                    ) {
                        $urlExtra .= $productAttribute['attribute_id'] . '=' . $id . '&';
                    }
                }

                if (!empty($urlExtra)) {
                    $url = $url . '#' . rtrim($urlExtra, '&');
                }
            }
        }

        return $url;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param                            $config
     *
     * @return array|string
     */
    public function getProductImage($product, $config)
    {
        $imageData = array();
        if (!empty($config['image_resize']) && !empty($config['image_size'])) {
            $imageFile = $product->getData($config['image_source']);
            $imageModel = Mage::getModel('catalog/product_image')
                ->setSize($config['image_size'])
                ->setDestinationSubdir($config['image_source'])
                ->setBaseFile($imageFile);
            if (!$imageModel->isCached()) {
                $imageModel->resize()->saveFile();
            }

            $productImage = $imageModel->getUrl();
            return (string)$productImage;
        } else {
            $image = '';
            if (!empty($config['media_attributes'])) {
                foreach ($config['media_attributes'] as $mediaAtt) {
                    if ($mediaAtt == 'base') {
                        $mediaAtt = 'image';
                    }

                    $mediaData = $product->getData($mediaAtt);
                    if (!empty($mediaData)) {
                        if ($mediaData != 'no_selection') {
                            $image = $this->checkImagePath($mediaData, $config['media_image_url']);
                            $imageData['image'][$mediaAtt] = $image;
                        }
                    }
                }
            } else {
                if ($product->getThumbnail()) {
                    if ($product->getThumbnail() != 'no_selection') {
                        $image = $this->checkImagePath($product->getThumbnail(), $config['media_image_url']);
                        $imageData['image']['thumbnail'] = $image;
                    }
                }

                if ($product->getSmallImage()) {
                    if ($product->getSmallImage() != 'no_selection') {
                        $image = $this->checkImagePath($product->getSmallImage(), $config['media_image_url']);
                        $imageData['image']['small_image'] = $image;
                    }
                }

                if ($product->getImage()) {
                    if ($product->getImage() != 'no_selection') {
                        $image = $this->checkImagePath($product->getImage(), $config['media_image_url']);
                        $imageData['image']['image'] = $image;
                    }
                }
            }

            if (!empty($config['images'])) {
                $imageData['image_link'] = $image;
                $container = new Varien_Object(array('attribute' => new Varien_Object(array('id' => $config['media_gallery_id']))));
                $imgProduct = new Varien_Object(array('id' => $product->getId(), 'store_id' => $config['store_id']));
                $gallery = Mage::getResourceModel('catalog/product_attribute_backend_media')
                    ->loadGallery($imgProduct, $container);
                $i = 1;
                usort(
                    $gallery, function ($a, $b) {
                    return $a['position_default'] > $b['position_default'];
                }
                );
                foreach ($gallery as $galImage) {
                    if ($galImage['disabled'] == 0) {
                        $imageData['image']['all']['image_' . $i] = $this->checkImagePath($galImage['file'],
                            $config['media_image_url']);
                        $imageData['image']['last'] = $this->checkImagePath($galImage['file'],
                            $config['media_image_url']);
                        if ($i == 1) {
                            $imageData['image']['first'] = $this->checkImagePath($galImage['file'],
                                $config['media_image_url']);
                        }

                        $i++;
                    }
                }

                return $imageData;
            } else {
                if (!empty($imageData['image']['image'])) {
                    return $imageData['image']['image'];
                } else {
                    return $image;
                }
            }
        }
    }

    /**
     * @param $path
     * @param $mediaUrl
     *
     * @return string
     */
    public function checkImagePath($path, $mediaUrl)
    {
        if (substr($path, 0, 4) === 'http') {
            return $path;
        }

        if ($path[0] != '/') {
            return $mediaUrl . '/' . $path;
        } else {
            return $mediaUrl . $path;
        }
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param                            $config
     *
     * @return bool
     */
    public function getProductCondition($product, $config)
    {
        if (isset($config['condition_attribute'])) {
            if ($condition = $product->getAttributeText($config['condition_attribute'])) {
                return $condition;
            } else {
                return false;
            }
        }

        if (!empty($config['condition_default'])) {
            return $config['condition_default'];
        }

        return false;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param                            $config
     *
     * @return bool
     */
    public function getProductAvailability($product, $config)
    {
        if (!empty($config['stock_instock'])) {
            if ($product->getUseConfigManageStock()) {
                $manageStock = $config['stock_manage'];
            } else {
                $manageStock = $product->getManageStock();
            }

            if ($manageStock) {
                if ($product['stock_status']) {
                    $availability = $config['stock_instock'];
                } else {
                    $availability = $config['stock_outofstock'];
                }
            } else {
                $availability = $config['stock_instock'];
            }

            return $availability;
        }

        return false;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param                            $config
     *
     * @return bool|string
     */
    public function getProductWeight($product, $config)
    {
        if (!empty($config['weight'])) {
            $weight = number_format($product->getWeight(), 2, '.', '');
            if (isset($config['weight_units'])) {
                $weight = $weight . ' ' . $config['weight_units'];
            }
            if (!empty($weight)) {
                return $weight;
            }
        }

        return '';
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param                            $config
     *
     * @return array
     */
    public function getProductPrice($product, $config)
    {
        $priceData = array();
        $priceMarkup = $this->getPriceMarkup($config);
        $taxParam = $config['use_tax'];

        if (!empty($config['hide_currency'])) {
            $currency = '';
        } else {
            $currency = ' ' . $config['currency'];
        }

        if (!empty($config['price_scope'])) {
            $price = Mage::getResourceModel('catalog/product')
                ->getAttributeRawValue($product->getId(), 'price', $config['store_id']);
        } else {
            $price = $product->getPrice();
        }

        if ($price == 0) {
            $price = $product->getMinPrice();
        }

        $price = Mage::helper('tax')->getPrice($product, $price, $taxParam);
        $priceData['regular_price'] = number_format(($price * $priceMarkup), 2, '.', '') . $currency;
        $pricerulePrice = Mage::helper('tax')->getPrice($product, $product->getFinalPrice(), $taxParam);

        if (($pricerulePrice > 0) && ($pricerulePrice < $price)) {
            $salesPrice = $pricerulePrice;
            $specialPriceFromDate = $product->getSpecialFromDate();
            $specialPriceToDate = $product->getSpecialToDate();
            $today = time();
            if ($today >= strtotime($specialPriceFromDate)) {
                if ($today <= strtotime($specialPriceToDate) || empty($specialPriceToDate)) {
                    $priceData['sales_date_start'] = $specialPriceFromDate;
                    $priceData['sales_date_end'] = $specialPriceToDate;
                }
            }
        }

        if (($product->getTypeId() == 'bundle') && ($price < 0.01)) {
            $price = $this->getPriceBundle($product, $config['store_id']);
        }

        if ($product->getTypeId() == 'grouped') {
            if (!empty($config['price_grouped'])) {
                $price = $this->getPriceGrouped($product, $config['price_grouped']);
            } else {
                if ($price < 0.01) {
                    $price = $this->getPriceGrouped($product);
                }
            }
        }

        $priceData['final_price_clean'] = $price;
        $priceData['price'] = number_format(($price * $priceMarkup), 2, '.', '') . $currency;

        if ($product->getMinPrice() !== null) {
            $minPrice = Mage::helper('tax')->getPrice($product, $product->getMinPrice(), $taxParam);
            $priceData['min_price'] = number_format(($minPrice * $priceMarkup), 2, '.', '') . $currency;
        }

        if ($product->getMaxPrice() !== null) {
            $maxPrice = Mage::helper('tax')->getPrice($product, $product->getMaxPrice(), $taxParam);
            $priceData['max_price'] = number_format(($maxPrice * $priceMarkup), 2, '.', '') . $currency;
        }

        if (isset($salesPrice)) {
            $priceData['sales_price'] = number_format(($salesPrice * $priceMarkup), 2, '.', '') . $currency;
        }

        return $priceData;
    }

    /**
     * @param $config
     *
     * @return int
     */
    public function getPriceMarkup($config)
    {
        $markup = 1;
        if (!empty($config['price_add_tax']) && !empty($config['price_add_tax_perc'])) {
            $markup = 1 + ($config['price_add_tax_perc'] / 100);
        }

        if ($config['base_currency_code'] != $config['currency']) {
            $exchangeRate = Mage::helper('directory')
                ->currencyConvert(1, $config['base_currency_code'], $config['currency']);
            $markup = ($markup * $exchangeRate);
        }

        return $markup;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param                            $storeId
     *
     * @return int
     */
    public function getPriceBundle($product, $storeId)
    {
        if (($product->getPriceType() == '1') && ($product->getFinalPrice() > 0)) {
            $price = $product->getFinalPrice();
        } else {
            $block = Mage::getSingleton('core/layout')->createBlock('bundle/catalog_product_view_type_bundle');
            $options = $block->setProduct($product)->getOptions();
            $price = 0;

            foreach ($options as $option) {
                $selection = $option->getDefaultSelection();
                if ($selection === null) {
                    continue;
                }

                $selectionProductId = $selection->getProductId();
                $_resource = Mage::getSingleton('catalog/product')->getResource();
                $finalPrice = $_resource->getAttributeRawValue($selectionProductId, 'final_price', $storeId);
                $selectionQty = $_resource->getAttributeRawValue($selectionProductId, 'selection_qty', $storeId);
                $price += ($finalPrice * $selectionQty);
            }
        }

        if ($price < 0.01) {
            $price = Mage::helper('tax')->getPrice($product, $product->getFinalPrice(), true);
        }

        return $price;
    }

    /**
     * @param        Mage_Catalog_Model_Product $product
     * @param string                            $pricemodel
     *
     * @return bool|mixed|number
     */
    public function getPriceGrouped($product, $pricemodel = '')
    {
        if (!$pricemodel) {
            $pricemodel = 'min';
        }

        $prices = array();
        $_associatedProducts = $product->getTypeInstance(true)->getAssociatedProducts($product);
        foreach ($_associatedProducts as $_item) {
            $priceAssociated = Mage::helper('tax')->getPrice($_item, $_item->getFinalPrice(), true);
            if ($priceAssociated > 0) {
                $prices[] = $priceAssociated;
            }
        }

        if (!empty($prices)) {
            if ($pricemodel == 'min') {
                return min($prices);
            }

            if ($pricemodel == 'max') {
                return max($prices);
            }

            if ($pricemodel == 'total') {
                return array_sum($prices);
            }
        }

        return false;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return bool|string
     */
    public function getProductBundle($product)
    {
        if ($product->getTypeId() == 'bundle') {
            return 'true';
        }
        return false;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param                            $config
     *
     * @return string
     */
    public function getIsInStock($product, $config)
    {
        if ($product->getUseConfigManageStock()) {
            $manageStock = $config['stock_manage'];
        } else {
            $manageStock = $product->getManageStock();
        }

        if ($manageStock) {
            return $product->getIsInStock();
        } else {
            return "1";
        }
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param                            $data
     * @param array                      $config
     *
     * @return string
     */
    public function getProductData($product, $data, $config = array())
    {
        $type = $data['type'];
        $source = $data['source'];
        $value = '';
        switch ($type) {
            case 'price':
                if (!empty($product[$source])) {
                    $value = number_format($product[$source], 2, '.', '');
                    if (!empty($config['currency'])) {
                        $value .= ' ' . $config['currency'];
                    }
                }
                break;
            case 'select':
                if (!empty($source)) {
                    $value = $product->getAttributeText($source);
                }
                break;
            case 'multiselect':
                if (is_array($product->getAttributeText($source))) {
                    $attributetext = implode(',', $product->getAttributeText($source));
                } else {
                    $attributetext = $product->getAttributeText($source);
                }
                if (!empty($attributetext)) {
                    $value = $attributetext;
                }
                break;
            case 'float':
                if (!empty($source) && isset($product[$source])) {
                    $value = round(floatval($product[$source]));
                }
                break;
            case 'boolean':
                $value = 0;
                if (!empty($source) && isset($product[$source])) {
                    $value = (int)$product[$source];
                }
                break;
            default:
                if (isset($product[$source])) {
                    $value = $product[$source];
                }
                break;
        }

        return $value;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return mixed
     */
    public function getAttributeSetName($product)
    {
        return Mage::getModel('eav/entity_attribute_set')->load($product->getAttributeSetId())->getAttributeSetName();
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param                            $bundleStock
     *
     * @return mixed|null
     */
    public function getStock($product, $bundleStock)
    {
        if ($product->getTypeId() != 'bundle') {
            return $product->getData('qty');
        }

        if ($bundleStock) {
            $avQty = array();
            $block = Mage::getSingleton('core/layout')->createBlock('bundle/catalog_product_view_type_bundle');
            $options = $block->setProduct($product)->getOptions();
            foreach ($options as $option) {
                if ($option->getRequired()) {
                    $selection = $option->getDefaultSelection();
                    if ($selection === null) {
                        continue;
                    }
                    if ($selection->getSelectionQty() > 0) {
                        $avQty[] = ($selection->getStockItem()->getQty() / $selection->getSelectionQty());
                    }
                }
            }

            if (!empty($avQty)) {
                return min($avQty);
            } else {
                return null;
            }
        }

        return null;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param $config
     *
     * @return mixed
     */
    public function getBackordersEnabled($product, $config)
    {
        if ($product->getUseConfigManageStock()) {
            $manageStock = $config['stock_manage'];
        } else {
            $manageStock = $product->getManageStock();
        }

        if (!$manageStock) {
            return "0";
        }

        if ($product->getUseConfigBackorders()) {
            return $config['backorders'];
        }

        return $product->getBackorders();
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param                            $config
     *
     * @return array
     */
    public function getProductCategories($product, $config)
    {
        if (isset($config['category_data'])) {
            $categoryData = $config['category_data'];
            $productsCat = array();
            $categoryIds = $product->getCategoryIds();
            if (!empty($config['category_full'])) {
                $path = array();
                foreach ($categoryIds as $categoryId) {
                    if (isset($categoryData[$categoryId])) {
                        $path[] = $categoryData[$categoryId]['name'];
                    }
                }

                $productsCat = array('path' => $path);
            } else {
                foreach ($categoryIds as $categoryId) {
                    if (isset($categoryData[$categoryId])) {
                        $productsCat[] = $categoryData[$categoryId];
                    }
                }
            }

            return $this->getSortedArray($productsCat, 'level');
        }
    }

    /**
     * @param $data
     * @param $sort
     *
     * @return array
     */
    function getSortedArray($data, $sort)
    {
        $code = "return strnatcmp(\$a['$sort'], \$b['$sort']);";
        usort($data, create_function('$a,$b', $code));

        return array_reverse($data);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return string
     */
    public function getProductCategoryIds($product)
    {
        return implode(',', $product->getCategoryIds());
    }

    /**
     * @param        $st
     * @param string $action
     *
     * @return mixed|string
     */
    public function cleanData($st, $action = '')
    {
        if ($action) {
            $actions = explode('_', $action);
            if (in_array('striptags', $actions)) {
                $st = $this->stripTags($st);
                $st = trim($st);
            }

            if (in_array('replacetags', $actions)) {
                $st = str_replace(array("\r", "\n"), "", $st);
                $st = str_replace(array("<br>", "<br/>", "<br />"), '\n', $st);
                $st = $this->stripTags($st);
                $st = rtrim($st);
            }

            if (in_array('replacetagsn', $actions)) {
                $st = str_replace(array("\r", "\n"), "", $st);
                $st = str_replace(array("<br>", "<br/>", "<br />"), '\\' . '\n', $st);
                $st = $this->stripTags($st);
                $st = rtrim($st);
            }

            if (in_array('rn', $actions)) {
                $st = str_replace(array("\r", "\n"), "", $st);
            }

            if (in_array('truncate', $actions)) {
                $st = Mage::helper('core/string')->truncate($st, '5000');
            }

            if (in_array('truncate150', $actions)) {
                $st = Mage::helper('core/string')->truncate($st, '150');
            }

            if (in_array('uppercheck', $actions)) {
                if (strtoupper($st) == $st) {
                    $st = ucfirst(strtolower($st));
                }
            }

            if (in_array('cdata', $actions)) {
                $st = '<![CDATA[' . $st . ']]>';
            }

            if (in_array('round', $actions)) {
                if (!empty($actions[1])) {
                    if ($st > $actions[1]) {
                        $st = $actions[1];
                    }
                }

                $st = round($st);
            }

            if (in_array('boolean', $actions)) {
                ($st > 0 ? $st = 1 : $st = 0);
            }
        }

        return $st;
    }

    /**
     * @param $parents
     * @param $config
     *
     * @return array
     */
    public function getConfigurableAttributesAsArray($parents, $config)
    {
        $configurableAttributes = array();

        if (empty($parents)) {
            return $configurableAttributes;
        }

        if (empty($config['conf_switch_urls'])) {
            return $configurableAttributes;

        }

        foreach ($parents as $parent) {
            if ($parent->getTypeId() == 'configurable') {
                $configurableAttributes[$parent->getEntityId()] = $parent->getTypeInstance(true)
                    ->getConfigurableAttributesAsArray($parent);
            }
        }

        return $configurableAttributes;
    }

    /**
     * @param $config
     *
     * @return string
     */
    public function getTaxUsage($config)
    {
        if (!empty($config['force_tax'])) {
            if ($config['force_tax'] == 'incl') {
                return 'true';
            } else {
                return '';
            }
        } else {
            return 'true';
        }
    }

    /**
     * @param       $attributes
     * @param array $config
     *
     * @return mixed
     */
    public function addAttributeData($attributes, $config = array())
    {
        foreach ($attributes as $key => $attribute) {
            $type = (!empty($attribute['type']) ? $attribute['type'] : '');
            $action = (!empty($attribute['action']) ? $attribute['action'] : '');
            $parent = (!empty($attribute['parent']) ? $attribute['parent'] : '');
            if (isset($attribute['source'])) {
                $attributeModel = Mage::getModel('eav/entity_attribute')
                    ->loadByCode('catalog_product', $attribute['source']);

                $type = $attributeModel->getFrontendInput();
            }

            if (!empty($attribute['label']) && ($attribute['label'] == 'manage_stock')) {
                $type = 'boolean';
            }
            if (!empty($attribute['label']) && ($attribute['label'] == 'qty')) {
                $type = 'float';
            }

            if (!empty($config['conf_fields'])) {
                $confAttributes = explode(',', $config['conf_fields']);
                if (in_array($key, $confAttributes)) {
                    $parent = '1';
                }
            }

            $attributes[$key] = array(
                'label'  => $attribute['label'],
                'source' => $attribute['source'],
                'type'   => $type,
                'action' => $action,
                'parent' => $parent
            );
        }

        return $attributes;
    }

    /**
     * @param $config
     * @param $storeId
     *
     * @return array
     */
    public function getCategoryData($config, $storeId)
    {
        $defaultAttributes = array('entity_id', 'path', 'name', 'level');
        $attributes = $defaultAttributes;

        if (!empty($config['category_custom'])) {
            $attributes[] = $config['category_custom'];
        }

        if (!empty($config['category_replace'])) {
            $attributes[] = $config['category_replace'];
        }

        if (!empty($config['category_exclude'])) {
            $attributes[] = $config['category_exclude'];
        }

        // CHECK IF NEW ATTRIBUTES ARE AVAILABLE
        try {
            Mage::getModel('catalog/category')
                ->setStoreId($storeId)
                ->getCollection()
                ->addAttributeToSelect($attributes)
                ->setCurPage(1)
                ->setPageSize(1)
                ->getFirstItem();
        } catch (Exception $e) {
            Mage::log($e->getMessage());
        }

        if (empty($e)) {
            /** @var Mage_Catalog_Model_Resource_Category_Collection $categories */
            $categories = Mage::getModel('catalog/category')
                ->setStoreId($storeId)
                ->getCollection()
                ->addAttributeToSelect($attributes)
                ->addFieldToFilter('is_active', array('eq' => 1));
        } else {
            /** @var Mage_Catalog_Model_Resource_Category_Collection $categories */
            $categories = Mage::getModel('catalog/category')
                ->setStoreId($storeId)
                ->getCollection()
                ->addAttributeToSelect($defaultAttributes)
                ->addFieldToFilter('is_active', array('eq' => 1));
        }

        if (!empty($config['filter_enabled'])) {
            $type = $config['filter_type'];
            $fCategories = explode(',', $config['filter_cat']);
            if ($type && $fCategories) {
                if ($type == 'include') {
                    $categories->addAttributeToFilter('entity_id', array('in' => $fCategories));
                } else {
                    $categories->addAttributeToFilter('entity_id', array('nin' => $fCategories));
                }
            }
        }

        $_categories = array();

        foreach ($categories as $cat) {
            $custom = '';
            $name = '';
            $exclude = 0;
            if (!empty($config['category_replace'])) {
                if (!empty($cat[$config['category_replace']])) {
                    $name = $cat[$config['category_replace']];
                }
            }

            if (isset($config['category_custom'])) {
                if (!empty($cat[$config['category_custom']])) {
                    $custom = $cat[$config['category_custom']];
                }
            }

            if (isset($config['category_exclude'])) {
                if (!empty($cat[$config['category_exclude']])) {
                    $exclude = $cat[$config['category_exclude']];
                }
            }

            if (empty($name)) {
                $name = $cat['name'];
            }

            if ($exclude != 1) {
                $_categories[$cat->getId()] = array(
                    'path'    => $cat['path'],
                    'custom'  => $custom,
                    'name'    => $name,
                    'level'   => $cat['level'],
                    'exclude' => $exclude
                );
            }
        }

        foreach ($_categories as $key => $cat) {
            $path = array();
            $customPath = array();
            $paths = explode('/', $cat['path']);
            foreach ($paths as $p) {
                if (!empty($_categories[$p]['name'])) {
                    if ($_categories[$p]['level'] > 1) {
                        $path[] = $_categories[$p]['name'];
                        if (!empty($_categories[$p]['custom'])) {
                            $customPath[] = $_categories[$p]['custom'];
                        }
                    }
                }
            }

            $_categories[$key] = array(
                'path'        => $this->cleanData($path, 'stiptags'),
                'custom_path' => $this->cleanData($customPath, 'stiptags'),
                'custom'      => $this->cleanData(end($customPath), 'striptags'),
                'name'        => $this->cleanData($cat['name'], 'striptags'),
                'level'       => $cat['level']
            );
        }

        return $_categories;
    }

    /**
     * @param $products
     * @param $config
     *
     * @return array
     */
    public function getParentsFromCollection($products, $config)
    {
        $ids = array();
        if (empty($config['conf_enabled'])) {
            return $ids;
        }

        foreach ($products as $product) {
            if ($parentId = $this->getParentData($product)) {
                $ids[$product->getEntityId()] = $parentId;
            }
        }

        return $ids;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return array
     */
    public function getParentData($product)
    {
        if (($product['type_id'] == 'simple')) {
            $configIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            if ($configIds) {
                return $configIds;
            }

            $groupIds = Mage::getResourceSingleton('catalog/product_link')->getParentIdsByChild(
                $product->getId(),
                Mage_Catalog_Model_Product_Link::LINK_TYPE_GROUPED
            );

            if ($groupIds) {
                return $groupIds;
            }
        }
    }

    /**
     * @param $config
     * @param $products
     *
     * @return array
     */
    public function getTypePrices($config, $products)
    {
        $typePrices = array();
        $confEnabled = $config['conf_enabled'];
        $simplePrice = $config['simple_price'];

        if (empty($products)) {
            return $typePrices;
        }

        if (!empty($confEnabled) && empty($simplePrice)) {
            foreach ($products as $product) {
                if ($product->getTypeId() == 'configurable') {
                    $parentId = $product->getEntityId();
                    $attributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);
                    $basePrice = $product->getFinalPrice();
                    $basePriceReg = $product->getPrice();
                    $optionPrices = array();

                    foreach ($attributes as $attribute) {
                        $prices = $attribute->getPrices();
                        foreach ($prices as $value) {
                            $product->setConfigurablePrice(
                                $this->preparePrice(
                                    $value['pricing_value'],
                                    $value['is_percent'], $product
                                )
                            );
                            $product->setParentId(true);
                            Mage::dispatchEvent(
                                'catalog_product_type_configurable_price', array('product' => $product)
                            );
                            $configurablePrice = $product->getConfigurablePrice();
                            $optionPrices[$value['value_index']] = $configurablePrice;
                            $optionPrices[$value['value_index'] . '_reg'] =
                                $this->prepareOldPrice($value['pricing_value'], $value['is_percent'], $product);
                        }
                    }

                    $simple = $product->getTypeInstance()->getUsedProducts();
                    foreach ($simple as $sProduct) {
                        $totalPrice = $basePrice;
                        $totalPriceReg = $basePriceReg;
                        foreach ($attributes as $attribute) {
                            $value = $sProduct->getData($attribute->getProductAttribute()->getAttributeCode());
                            if (isset($optionPrices[$value])) {
                                $totalPrice += $optionPrices[$value];
                                $totalPriceReg += $optionPrices[$value . '_reg'];
                            }
                        }

                        $typePrices[$parentId . '_' . $sProduct->getEntityId()] =
                            number_format(($totalPrice * $config['markup']), 2, '.', '');

                        $typePrices[$parentId . '_' . $sProduct->getEntityId() . '_reg'] =
                            number_format(($totalPriceReg * $config['markup']), 2, '.', '');
                    }
                }
            }
        }

        return $typePrices;
    }

    /**
     * @param                                 $price
     * @param bool                            $isPercent
     * @param      Mage_Catalog_Model_Product $product
     *
     * @return float|int
     */
    public function preparePrice($price, $isPercent = false, $product)
    {
        if ($isPercent && !empty($price)) {
            $price = $product->getFinalPrice() * $price / 100;
        }

        return $price;
    }

    /**
     * @param                                 $price
     * @param bool                            $isPercent
     * @param      Mage_Catalog_Model_Product $product
     *
     * @return float|int
     */
    public function prepareOldPrice($price, $isPercent = false, $product)
    {
        if ($isPercent && !empty($price)) {
            $price = $product->getPrice() * $price / 100;
        }

        return $price;
    }

    /**
     * @param $dir
     *
     * @return bool
     */
    public function checkOldVersion($dir)
    {
        if ($dir) {
            $dir = Mage::getBaseDir('app') . DS . 'code' . DS . 'local' . DS . 'Magmodules' . DS . $dir;
            return file_exists($dir);
        }
    }

    /**
     * @param $attributes
     *
     * @return array
     */
    public function checkFlatCatalog($attributes)
    {
        $nonFlatAttributes = array();
        $skipCheck = array('sku', 'category_ids');
        foreach ($attributes as $key => $attribute) {
            if (!empty($attribute['source'])) {
                if (($attribute['source'] != 'entity_id') && !in_array($attribute['source'], $skipCheck)) {
                    $_attribute = Mage::getModel('eav/entity_attribute')
                        ->loadByCode('catalog_product', $attribute['source']);
                    if ($_attribute->getUsedInProductListing() == 0) {
                        if ($_attribute->getId()) {
                            $nonFlatAttributes[$_attribute->getId()] = $_attribute->getFrontendLabel();
                        }
                    }
                }
            }
        }

        return $nonFlatAttributes;
    }

    /**
     * @param $storeId
     *
     * @return mixed|string
     */
    public function getProductUrlSuffix($storeId)
    {
        $suffix = Mage::getStoreConfig('catalog/seo/product_url_suffix', $storeId);
        if (!empty($suffix)) {
            if ((strpos($suffix, '.') === false) && ($suffix != '/')) {
                $suffix = '.' . $suffix;
            }
        }

        return $suffix;
    }

    /**
     * @return array
     */
    public function getMediaAttributes()
    {
        $mediaTypes = array();
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addFieldToFilter('frontend_input', 'media_image');
        foreach ($attributes as $attribute) {
            $mediaTypes[] = $attribute->getData('attribute_code');
        }

        return $mediaTypes;
    }

    /**
     * @return int
     */
    public function getStoreIdConfig()
    {
        $storeId = 0;
        $code = Mage::getSingleton('adminhtml/config_data')->getStore();
        if (!empty($code)) {
            $storeId = Mage::getModel('core/store')->load($code)->getId();
        }

        return $storeId;
    }

    /**
     * @return bool|mixed
     */
    public function getToken()
    {
        $token = $this->getUncachedConfigValue('channable/connect/token', 0);
        $token = Mage::helper('core')->decrypt($token);
        $strlen = strlen($token);

        if ($strlen == 32 || $strlen == 16) {
            if (ctype_alnum($token)) {
                return $token;
            }
        }

        return false;
    }

    /**
     * @param     $path
     * @param int $storeId
     *
     * @return mixed
     */
    public function getUncachedConfigValue($path, $storeId = 0)
    {
        $collection = Mage::getModel('core/config_data')->getCollection()->addFieldToFilter('path', $path);
        if ($storeId == 0) {
            $collection = $collection->addFieldToFilter('scope_id', 0)->addFieldToFilter('scope', 'default');
        } else {
            $collection = $collection->addFieldToFilter('scope_id', $storeId)->addFieldToFilter('scope', 'stores');
        }

        return $collection->getFirstItem()->getValue();
    }

    /**
     * @param $page
     * @param $pages
     * @param $processed
     */
    public function addLog($page, $pages, $processed)
    {
        $memoryUsage = memory_get_usage(true);
        if ($memoryUsage < 1024) {
            $usage = $memoryUsage . ' b';
        } elseif ($memoryUsage < 1048576) {
            $usage = round($memoryUsage / 1024, 2) . ' KB';
        } else {
            $usage = round($memoryUsage / 1048576, 2) . ' MB';
        }

        $msg = sprintf(
            'Page: %s/%s | Memory Usage: %s | Products: %s',
            $page,
            $pages,
            $usage,
            $processed
        );

        Mage::log($msg, null, self::LOG_FILENAME, self::FORCE_LOG);
    }

    /**
     * @param $timeStart
     *
     * @return float|string
     */
    public function getTimeUsage($timeStart)
    {

        $time = round((microtime(true) - $timeStart));
        if ($time > 120) {
            $time = round($time / 60, 1) . ' ' . $this->__('minutes');
        } else {
            $time = round($time) . ' ' . $this->__('seconds');
        }

        return $time;
    }
}
