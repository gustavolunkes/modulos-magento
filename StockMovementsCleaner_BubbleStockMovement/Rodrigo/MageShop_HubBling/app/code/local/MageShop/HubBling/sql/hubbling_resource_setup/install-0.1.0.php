<?php

$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();
$tableSyncProduct = $installer->getTable('hubbling/syncProduct');

// Drop da tabela antiga (se existir)
$connection->dropTable($tableSyncProduct);

// Criar nova tabela com as colunas corretas
$table = $connection->newTable($tableSyncProduct)
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary'  => true
    ], 'ID')
    ->addColumn('sku', Varien_Db_Ddl_Table::TYPE_TEXT, 255, [
        'nullable' => false
    ], 'SKU do Produto')
    ->addColumn('api_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, [
        'nullable' => true
    ], 'Id do Produto Bling')
    ->addColumn('exist_bling', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, [
        'nullable' => false,
        'default'  => 0
    ], 'Existe no Bling')
    ->addColumn('sync', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, [
        'nullable' => false,
        'default'  => 0
    ], 'Sincronizado')
    ->addColumn('depositos', Varien_Db_Ddl_Table::TYPE_TEXT, 255, [
        'nullable' => true
    ], 'Depositos')
    // ->addColumn('qty_inventory', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
    //     'nullable' => false,
    //     'default'  => 0
    // ], 'Quantidade em Estoque')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, [
        'nullable' => false,
        'default'  => Varien_Db_Ddl_Table::TIMESTAMP_INIT
    ], 'Criado em')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, [
        'nullable' => false,
        'default'  => Varien_Db_Ddl_Table::TIMESTAMP_INIT_UPDATE
    ], 'Atualizado em')
    ->setComment('Controle de sincronização de produtos');

$installer->getConnection()->createTable($table);

$installer->endSetup();