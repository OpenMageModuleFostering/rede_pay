<?php

class Rede_Pay_Controller_Adminhtml_Action extends Mage_Adminhtml_Controller_Action
{

    use Rede_Pay_Trait_Data;
    

    /**
     * @return Mage_Sales_Model_Order
     */
    protected function _initOrder($orderId = null)
    {
        if (empty($orderId)) {
            $orderId = $this->getRequest()->getParam('order_id');
        }

        if (!$orderId) {
            $this->_forward('noRoute');
            return false;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);

        if (!$order->getId()) {
            $this->_forward('noRoute');
            return false;
        }

        if (!Mage::registry('current_order')) {
            Mage::register('current_order', $order);
        }

        return $order;
    }

}
