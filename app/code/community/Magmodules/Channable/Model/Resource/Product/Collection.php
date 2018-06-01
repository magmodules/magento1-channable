<?php
/**
 * Magmodules.eu - http://www.magmodules.eu.
 *
 * NOTICE OF LICENSE
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.magmodules.eu/MM-LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magmodules.eu so we can send you a copy immediately.
 *
 * @category      Magmodules
 * @package       Magmodules_Channable
 * @author        Magmodules <info@magmodules.eu>
 * @copyright     Copyright (c) 2018 (http://www.magmodules.eu)
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Magmodules_Channable_Model_Resource_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{

    /**
     * @return bool
     */
    public function isEnabledFlat()
    {
        return false;
    }

    /**
     * Force Bypass Flat
     * Initialize resources
     */
    protected function _construct()
    {
        $this->_init('catalog/product');
        $this->_initTables();
    }

}
