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

$installer = new Mage_Catalog_Model_Resource_Eav_Mysql4_Setup('core_setup');
$installer->startSetup();

$installer->addAttribute(
    'catalog_category', 'channable_exclude', array(
        'group'        => 'Feeds',
        'input'        => 'select',
        'type'         => 'int',
        'source'       => 'eav/entity_attribute_source_boolean',
        'label'        => 'Exclude Category for Channable',
        'required'     => false,
        'user_defined' => true,
        'visible'      => true,
        'global'       => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
        'position'     => 99,
    )
);

$installer->endSetup();