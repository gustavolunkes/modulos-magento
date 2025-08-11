<?php

$installer = $this;
$installer->startSetup();

$entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();

/**
 * Atributo: produto_eh_brinde (booleano)
 */
$installer->addAttribute('catalog_product', 'produto_eh_brinde', array(
    'group'             => 'Configurações',
    'label'             => 'Produto é Brinde?',
    'type'              => 'int',
    'input'             => 'boolean',
    'source'            => 'eav/entity_attribute_source_boolean',
    'default'           => 0,
    'required'          => false,
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'           => true,
    'user_defined'      => true,
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'visible_on_front'  => false,
    'unique'            => false,
    'is_configurable'   => false,
    'used_in_product_listing' => true
));

/**
 * Atributo: brindes_para_skus (text)
 */
$installer->addAttribute('catalog_product', 'brindes_para_skus', array(
    'group'             => 'Configurações',
    'label'             => 'Brindes para SKUs',
    'type'              => 'text',
    'input'             => 'textarea',
    'default'           => '',
    'required'          => false,
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'           => true,
    'user_defined'      => true,
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'visible_on_front'  => false,
    'unique'            => false,
    'is_configurable'   => false,
    'used_in_product_listing' => true
));

/**
 * Adiciona o campo is_freegift na tabela sales_flat_quote_item
 */
$installer->getConnection()
    ->addColumn(
        $installer->getTable('sales/quote_item'),
        'is_freegift',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'nullable' => false,
            'default'  => 0,
            'comment'  => 'Indica se o item é um brinde'
        )
    );

$installer->endSetup();
