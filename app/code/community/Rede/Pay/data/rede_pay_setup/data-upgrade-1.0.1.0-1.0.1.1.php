<?php
/**
 * To avoid problems with older orders we update the method in database from 'rede_clickpag' to 'rede_pay'.
 *
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * @var Mage_Core_Model_Resource_Setup $this
 * @var Magento_Db_Adapter_Pdo_Mysql   $connection
 */
$installer  = $this;
$connection = $installer->getConnection();

$bind  = ['method' => 'rede_pay'];
$where = new Zend_Db_Expr("method = 'rede_clickpag'");

$connection->update($installer->getTable('sales/order_payment'), $bind, $where);

/**
 * Now it's necessary to transfer all the data between the old and the new table.
 */
$oldTable = $installer->getTable('rede_pay/payments_deprecated');
$newTable = $installer->getTable('rede_pay/payments');

/** If the table does not exits then we do not need to proceed with this operation */
if (!$connection->isTableExists($oldTable)) {
    return;
}

/** @var Varien_Db_Select $select */
$fields   = ['order_id', 'order_increment_id', 'payment_id', 'transaction_id', 'additional_information_serialized'];
$select   = $connection->select()->from($oldTable, $fields);
$payments = $connection->fetchAll($select);

$amount   = 100;
$size     = ceil(count($payments) / $amount);

for ($y = 1; $y <= $size; $y++) {
    try {
        $rows = array_splice($payments, 0, $amount);
        $connection->insertMultiple($newTable, $rows);
    } catch (Exception $e) {
        Mage::logException($e);
        processSeparated($rows, $newTable, $connection);
    }
}


/**
 * In case of failure in the group insertion above we try to insert each one.
 *
 * @param array                        $rows
 * @param string                       $table
 * @param Magento_Db_Adapter_Pdo_Mysql $connection
 *
 * @throws Zend_Db_Adapter_Exception
 */
function processSeparated($rows, $table, Magento_Db_Adapter_Pdo_Mysql $connection) {
    foreach ($rows as $row) {
        $connection->insertIgnore($table, $row);
    }
}
