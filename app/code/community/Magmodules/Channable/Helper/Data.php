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

class Magmodules_Channable_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @param $path
     * @return array
     */
    public function getStoreIds($path)
    {
        $storeIds = array();
        foreach (Mage::app()->getStores() as $store) {
            $storeId = Mage::app()->getStore($store)->getId();
            if (Mage::getStoreConfig($path, $storeId)) {
                $storeIds[] = $storeId;
            }
        }

        return $storeIds;
    }

    /**
     * @param $product
     * @param $config
     * @param $parent
     * @param $parentAttributes
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
     * @param $parent
     * @param $config
     * @param $product
     * @return bool
     */
    public function validateParent($parent, $config, $product)
    {
        return $this->validateProduct($product, $config, $parent);
    }

    /**
     * @param $product
     * @param $config
     * @param $parent
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
     * @param $field
     * @param $product
     * @param $config
     * @param string $actions
     * @param $parent
     * @return bool
     */
    public function getAttributeValue($field, $product, $config, $actions = '', $parent, $parentAttributes)
    {
        $data = $config['field'][$field];
        $productData = $product;

        if (!empty($parent)) {
            if (!empty($data['parent'])) {
                $productData = $parent;
            }
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
            case 'attribute_set_id':
                $value = $this->getAttributeSetName($productData);
                break;
            case 'categories':
                $value = $this->getProductCategories($productData, $config);
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

        if ((isset($actions)) && (!empty($value))) {
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

        if (!empty($value) || is_numeric($value)) {
            $dataRow[$data['label']] = $value;

            return $dataRow;
        }

        return false;
    }

    /**
     * @param $product
     * @param $config
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
     * @param $product
     * @param $config
     * @param $parent
     * @return string
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

        if (!empty($parent) && !empty($url)) {
            if (!empty($parentAttributes[$parent->getEntityId()])) {
                $storeId = $config['store_id'];
                $pId = $product->getId();
                $productAttributeOptions = $parentAttributes[$parent->getEntityId()];
                $urlExtra = '';
                foreach ($productAttributeOptions as $productAttribute) {
                    $attCode = $productAttribute['attribute_code'];
                    $id = Mage::getResourceModel('catalog/product')->getAttributeRawValue($pId, $attCode, $storeId);
                    if ($id > 0) {
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

    public function getConfigurableAttributesAsArray($parents, $config)
    {
        $configurableAttributes = array();
        if (!empty($config['conf_switch_urls'])) {
            foreach ($parents as $parent) {
                if ($parent->getTypeId() == 'configurable') {
                    $configurableAttributes[$parent->getEntityId()] = $parent->getTypeInstance(true)
                        ->getConfigurableAttributesAsArray($parent);
                }
            }
        }

        return $configurableAttributes;
    }

    /**
     * @param $product
     * @param $config
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
                            $image = $config['media_image_url'] . $mediaData;
                            $imageData['image'][$mediaAtt] = $image;
                        }
                    }
                }
            } else {
                if ($product->getThumbnail()) {
                    if ($product->getThumbnail() != 'no_selection') {
                        $image = $config['media_image_url'] . $product->getThumbnail();
                        $imageData['image']['thumbnail'] = $image;
                    }
                }

                if ($product->getSmallImage()) {
                    if ($product->getSmallImage() != 'no_selection') {
                        $image = $config['media_image_url'] . $product->getSmallImage();
                        $imageData['image']['small_image'] = $image;
                    }
                }

                if ($product->getImage()) {
                    if ($product->getImage() != 'no_selection') {
                        $image = $config['media_image_url'] . $product->getImage();
                        $imageData['image']['image'] = $image;
                    }
                }
            }

            if (!empty($config['images'])) {
                $imageData['image_link'] = $image;
                $container = new Varien_Object(
                    array(
                        'attribute' => new Varien_Object(array('id' => $config['media_gallery_id'])))
                );
                $imgProduct = new Varien_Object(array('id' => $product->getId(), 'store_id' => $config['store_id']));
                $gallery = Mage::getResourceModel('catalog/product_attribute_backend_media')->loadGallery(
                    $imgProduct,
                    $container
                );

                $i = 1;
                usort(
                    $gallery, function ($a, $b) {
                    return $a['position_default'] > $b['position_default'];
                    }
                );
                foreach ($gallery as $galleryImage) {
                    if ($galleryImage['disabled'] == 0) {
                        $imageData['image']['all']['image_' . $i] = $config['media_image_url'] . $galleryImage['file'];
                        $imageData['image']['last'] = $config['media_image_url'] . $galleryImage['file'];
                        if ($i == 1) {
                            $imageData['image']['first'] = $config['media_image_url'] . $galleryImage['file'];
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
     * @param $product
     * @param $config
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
     * @param $product
     * @param $config
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
     * @param $product
     * @param $config
     * @return bool|string
     */
    public function getProductWeight($product, $config)
    {
        if (!empty($config['weight'])) {
            $weight = number_format($product->getWeight(), 2, '.', '');
            if (isset($config['weight_units'])) {
                $weight = $weight . ' ' . $config['weight_units'];
            }

            return $weight;
        }

        return false;
    }

    /**
     * @param $product
     * @param $config
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
            $price = Mage::getResourceModel('catalog/product')->getAttributeRawValue(
                $product->getId(), 'price',
                $config['store_id']
            );
        } else {
            $price = $product->getPrice();
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

        if (isset($salesPrice)) {
            $priceData['sales_price'] = number_format(($salesPrice * $priceMarkup), 2, '.', '') . $currency;
        }

        return $priceData;
    }

    /**
     * @param $config
     * @return int
     */
    public function getPriceMarkup($config)
    {
        $markup = 1;
        if (!empty($config['price_add_tax']) && !empty($config['price_add_tax_perc'])) {
            $markup = 1 + ($config['price_add_tax_perc'] / 100);
        }

        if ($config['base_currency_code'] != $config['currency']) {
            $exchangeRate = Mage::helper('directory')->currencyConvert(
                1,
                $config['base_currency_code'],
                $config['currency']
            );
            $markup = ($markup * $exchangeRate);
        }

        return $markup;
    }

    /**
     * @param $product
     * @param $storeId
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
     * @param $product
     * @param string $pricemodel
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
     * @param $product
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
     * @param $product
     * @param $data
     * @param string $config
     * @return string
     */
    public function getProductData($product, $data, $config = '')
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
                $value = $product->getAttributeText($source);
                break;
            case 'multiselect':
                if (is_array($product->getAttributeText($source))) {
                    $value = implode(',', $product->getAttributeText($source));
                } else {
                    $value = $product->getAttributeText($source);
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
     * @param $product
     * @return mixed
     */
    public function getAttributeSetName($product)
    {
        return Mage::getModel('eav/entity_attribute_set')->load($product->getAttributeSetId())->getAttributeSetName();
    }

    /**
     * @param $product
     * @param $config
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
     * @return array
     */
    function getSortedArray($data, $sort)
    {
        $code = "return strnatcmp(\$a['$sort'], \$b['$sort']);";
        usort($data, create_function('$a,$b', $code));

        return array_reverse($data);
    }

    /**
     * @param $st
     * @param string $action
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
     * @param $config
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
     * @param $attributes
     * @param string $config
     * @return mixed
     */
    public function addAttributeData($attributes, $config = '')
    {
        foreach ($attributes as $key => $attribute) {
            $type = (!empty($attribute['type']) ? $attribute['type'] : '');
            $action = (!empty($attribute['action']) ? $attribute['action'] : '');
            $parent = (!empty($attribute['parent']) ? $attribute['parent'] : '');
            if (isset($attribute['source'])) {
                $attributeModel = Mage::getModel('eav/entity_attribute')->loadByCode(
                    'catalog_product',
                    $attribute['source']
                );
                $type = $attributeModel->getFrontendInput();
            }

            if (!empty($config['conf_fields'])) {
                $confAttributes = explode(',', $config['conf_fields']);
                if (in_array($key, $confAttributes)) {
                    $parent = '1';
                }
            }

            $attributes[$key] = array(
                'label' => $attribute['label'],
                'source' => $attribute['source'],
                'type' => $type,
                'action' => $action,
                'parent' => $parent
            );
        }

        return $attributes;
    }

    /**
     * @param $config
     * @param $storeId
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
            $categories = Mage::getModel('catalog/category')
                ->setStoreId($storeId)
                ->getCollection()
                ->addAttributeToSelect($attributes)
                ->addFieldToFilter('is_active', array('eq' => 1));
        } else {
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
                    'path' => $cat['path'],
                    'custom' => $custom,
                    'name' => $name,
                    'level' => $cat['level'],
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
                'path' => $this->cleanData($path, 'stiptags'),
                'custom_path' => $this->cleanData($customPath, 'stiptags'),
                'custom' => $this->cleanData(end($customPath), 'striptags'),
                'name' => $this->cleanData($cat['name'], 'striptags'),
                'level' => $cat['level']
            );
        }

        return $_categories;
    }

    /**
     * @param $product
     * @param $config
     * @return bool
     */
    public function getParentData($product, $config)
    {
        if (!empty($config['conf_enabled'])) {
            if (($product['type_id'] == 'simple')) {
                $configIds = Mage::getModel('catalog/product_type_configurable')
                    ->getParentIdsByChild($product->getId());
                $groupIds = Mage::getResourceSingleton('catalog/product_link')->getParentIdsByChild(
                    $product->getId(),
                    Mage_Catalog_Model_Product_Link::LINK_TYPE_GROUPED
                );
                if ($configIds) {
                    return $configIds[0];
                }

                if ($groupIds) {
                    return $groupIds[0];
                }
            }
        }

        return false;
    }

    /**
     * @param $config
     * @param $products
     * @return array
     */
    public function getTypePrices($config, $products)
    {
        $typePrices = array();
        $confEnabled = $config['conf_enabled'];
        $simplePrice = $config['simple_price'];

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
     * @param $price
     * @param bool $isPercent
     * @param $product
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
     * @param $price
     * @param bool $isPercent
     * @param $product
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
     * @return bool
     */
    public function checkOldVersion($dir)
    {
        if ($dir) {
            $dir = Mage::getBaseDir('app') . DS . 'code' . DS . 'local' . DS . 'Magmodules' . DS . $dir;

            return file_exists($dir);
        }

        return false;
    }

    /**
     * @param $attributes
     * @return array
     */
    public function checkFlatCatalog($attributes)
    {
        $nonFlatAttributes = array();
        foreach ($attributes as $key => $attribute) {
            if (!empty($attribute['source'])) {
                if (($attribute['source'] != 'entity_id') && ($attribute['source'] != 'sku')) {
                    $_attribute = Mage::getModel('eav/entity_attribute')->loadByCode(
                        'catalog_product',
                        $attribute['source']
                    );
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
     * @return array
     */
    public function getMediaAttributes()
    {
        $mediaTypes = array();
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')->addFieldToFilter(
            'frontend_input',
            'media_image'
        );
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
     * @param $storeId
     * @return string
     */
    public function getProductUrlSuffix($storeId)
    {
        $suffix = Mage::getStoreConfig('catalog/seo/product_url_suffix', $storeId);
        if (!empty($suffix)) {
            if (($suffix[0] != '.') && ($suffix != '/')) {
                $suffix = '.' . $suffix;
            }
        }

        return $suffix;
    }

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
}
