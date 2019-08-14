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
 * Drop Rede Pay Order ID column ----------------------------------------------------------------------------------------
 */
$table = $installer->getTable('rede_pay/payments');
$connection->dropColumn($table, 'rede_order_id');

$installer->endSetup();
