<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Resource_Payments
 */
class Rede_Pay_Model_Resource_Payments extends Mage_Core_Model_Resource_Db_Abstract
{

    use Rede_Pay_Trait_Data;

    protected function _construct()
    {
        $this->_init('rede_pay/payments', 'id');
    }


    /**
     * @param Mage_Sales_Model_Resource_Order_Collection $collection
     *
     * @return $this
     */
    public function appendPaymentInfoToOrderCollection(Mage_Sales_Model_Resource_Order_Collection &$collection)
    {
        $collection->getSelect()
            ->joinLeft(
                array('payments' => $this->getMainTable()),
                'payments.order_id = main_table.entity_id',
                array(
                    'rede_payment_id'     => 'payment_id',
                    'rede_transaction_id' => 'transaction_id'
                )
            );

        return $this;
    }


    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return $this
     */
    public function appendPaymentInfoToOrder(Mage_Sales_Model_Order &$order)
    {
        if (!$order->getId()) {
            return $this;
        }

        $bind = array(
            ':order_id' => $order->getId()
        );

        /** @var Magento_Db_Adapter_Pdo_Mysql $read */
        $read   = $this->_getReadAdapter();
        $select = $read->select()
            ->from($this->getMainTable(), array(
                'rede_payment_id'     => 'payment_id',
                'rede_transaction_id' => 'transaction_id'
            ))
            ->where('order_id = :order_id');

        $result = $read->fetchRow($select, $bind);

        if (!$result) {
            return $this;
        }

        $order->addData($result);

        return $this;
    }


}
