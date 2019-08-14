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
$connection->addColumn($table, 'additional_information_serialized', array(
    'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
    'comment'  => 'Additional Information',
    'nullable' => true,
));

$installer->endSetup();
