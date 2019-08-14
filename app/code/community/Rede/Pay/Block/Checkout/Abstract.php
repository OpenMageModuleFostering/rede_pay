<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Block_Checkout_Abstract
 */
abstract class Rede_Pay_Block_Checkout_Abstract extends Mage_Core_Block_Template
{

    use Rede_Pay_Trait_Data,
        Rede_Pay_Trait_Config;

    /**
     * @return null|string
     */
    public function getLastPaymentId()
    {
        if (!$this->hasData('payment_id')) {
            /** @var Rede_Pay_Model_Payments $payments */
            $payments = Mage::getModel('rede_pay/payments');
            $payments->loadByOrderId($this->getOrder()->getId());

            $this->setData('payment_id', $payments->getPaymentId());
        }

        return $this->getData('payment_id');
    }


    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->hasData('order')) {
            if (!($order = $this->_getCurrentOrder())) {
                $order = Mage::getModel('sales/order')->load($this->getOrderId());
            }

            $this->setData('order', $order);
        }

        return $this->getData('order');
    }


    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->_getLastOrderId();
    }


    /**
     * @return string
     */
    public function getRealOrderId()
    {
        return $this->getOrder()->getIncrementId();
    }


    /**
     * @param int $width
     *
     * @return string
     */
    public function getLogoHtml($width = null)
    {
        return $this->_helper()->getLogoHtml($width);
    }


    /**
     * @return Rede_Pay_Model_Session
     */
    public function getSession()
    {
        return $this->_getSession();
    }


    /**
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckoutSession()
    {
        return $this->_getCheckoutSession();
    }


    /**
     * @return string
     */
    public function getPrintOrderUrl()
    {
        return $this->getUrl('sales/order/print', array('order_id'=> $this->getOrderId()));
    }


    /**
     * @return bool
     */
    public function canReorder()
    {
        if ($this->getOrder()->getCustomerIsGuest()) {
            return true;
        }

        return $this->getOrder()->canReorderIgnoreSalable();
    }


    /**
     * @return string
     */
    public function getReorderUrl()
    {
        return $this->getUrl('*/*/reorder', array('order_id' => $this->getOrder()->getId()));
    }


    /**
     * @return bool
     */
    public function canRetryPayment()
    {
        try {
            /** @var Mage_Sales_Model_Order $order */
            $order = $this->getOrder();

            if (!$order || !$order->getId()) {
                return (bool) false;
            }

            /**
             * The order needs to be in one of the following states:
             *  - pending_payment
             *  - processing
             *  - new
             */
            $allowedStates = array (
                Mage_Sales_Model_Order::STATE_NEW,
                Mage_Sales_Model_Order::STATE_PROCESSING,
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
            );

            if (!in_array($order->getState(), $allowedStates)) {
                return (bool) false;
            }

            /**
             * If you cannot invoice the order (and unhold it as well) it can mean that it's already invoiced.
             */
            if (!$order->canInvoice() && ($order->getState() !== Mage_Sales_Model_Order::STATE_HOLDED)) {
                return (bool) false;
            }

            return (bool) true;
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return (bool) false;
    }


    /**
     * @return Mage_Sales_Model_Order|null
     */
    protected function _getCurrentOrder()
    {
        return Mage::registry('current_order');
    }

}
