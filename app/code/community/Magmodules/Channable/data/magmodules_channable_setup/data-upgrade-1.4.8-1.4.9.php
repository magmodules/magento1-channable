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

$token = Mage::getModel('core/config_data')->getCollection()
    ->addFieldToFilter('path', 'channable/connect/token')
    ->addFieldToFilter('scope_id', 0)
    ->addFieldToFilter('scope', 'default')
    ->getFirstItem()
    ->getValue();

if (empty($token)) {
    $chars = str_split("abcdefghijklmnopqrstuvwxyz0123456789");
    for ($i = 0; $i < 32; $i++) {
        $token .= $chars[array_rand($chars)];
    }
}

$tokenEncrypted = Mage::getModel('core/config_data')->getCollection()
    ->addFieldToFilter('path', 'channable/connect/token_encrypted')
    ->addFieldToFilter('scope_id', 0)
    ->addFieldToFilter('scope', 'default')
    ->getFirstItem()
    ->getValue();

if (empty($tokenEncrypted)) {
    $encrypt = Mage::helper('core')->encrypt($token);
    Mage::getModel('core/config')->saveConfig('channable/connect/token_encrypted', 1);
    Mage::getModel('core/config')->saveConfig('channable/connect/token', $encrypt);
}