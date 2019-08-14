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
 * Add Rede Pay Order ID column ----------------------------------------------------------------------------------------
 */
$table = $installer->getTable('rede_pay/payments');
$connection->addColumn($table, 'rede_order_id', array(
    'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'   => 100,
    'comment'  => 'Rede Pay Order ID.',
    'nullable' => true,
    'after'    => 'payment_id'
));

$installer->endSetup();
