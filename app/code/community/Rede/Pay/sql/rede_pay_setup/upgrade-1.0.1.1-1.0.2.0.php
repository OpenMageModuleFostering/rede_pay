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
 * Add Additional Information column -----------------------------------------------------------------------------------
 */
$table = $installer->getTable('rede_pay/payments');
$connection->addColumn($table, 'payment_retries', array(
    'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'default'  => 0,
    'comment'  => 'Payment Retries',
    'nullable' => false,
));

$installer->endSetup();
