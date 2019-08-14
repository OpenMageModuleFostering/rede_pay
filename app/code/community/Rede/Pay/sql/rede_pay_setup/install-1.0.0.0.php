<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * @var Mage_Core_Model_Resource_Setup $this
 */
$installer = $this;
$installer->startSetup();

/** @var Magento_Db_Adapter_Pdo_Mysql $connection */
$connection = $installer->getConnection();

/**
 * Create Table --------------------------------------------------------------------------------------------------------
 */
$tableName = $installer->getTable('rede_pay/payments');

/** Tries to drop table before trying to create it. */
$connection->dropTable($tableName);

/** @var Varien_Db_Ddl_Table $table */
$table = $connection->newTable($tableName);
$table->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
        'identity' => true,
        'primary'  => true,
        'nullable' => false
    ), 'Payment ID.')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
        'primary'  => true,
        'nullable' => false,
    ), 'Reference Order ID.')
    ->addColumn('order_increment_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 50, array(
        'nullable' => false,
    ), 'Reference Order Increment ID.')
    ->addColumn('payment_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 100, array(
        'nullable' => false
    ), 'Payment ID in Payment Gateway.')
    ->addColumn('transaction_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 100, array(
        'nullable' => true
    ), 'Transaction ID in Payment Gateway.');

/**
 * Add Indexes ---------------------------------------------------------------------------------------------------------
 */
$fields  = array('payment_id', 'order_id');
$idxType = Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE;
$idxName = $installer->getIdxName($tableName, $fields, $idxType);
$table->addIndex($idxName, $fields, array('type' => $idxType));

/**
 * Add Foreign Keys ----------------------------------------------------------------------------------------------------
 */
$refTable = $installer->getTable('sales/order');
$fkName   = $installer->getFkName($tableName, 'order_id', $refTable, 'entity_id');
$actCscd  = Varien_Db_Ddl_Table::ACTION_CASCADE;
$table->addForeignKey($fkName, 'order_id', $refTable, 'entity_id', $actCscd, $actCscd);

$connection->createTable($table);

$installer->endSetup();
