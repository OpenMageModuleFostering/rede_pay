<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Block_Info_Method_Standard
 */
class Rede_Pay_Block_Info_Method_Standard extends Mage_Payment_Block_Info
{

    use Rede_Pay_Trait_Data,
        Rede_Pay_Trait_Config;

    /** @var Rede_Pay_Model_Payments */
    protected $_payments = null;


    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('rede/pay/info/method/standard.phtml');
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
     * @return Rede_Pay_Model_Payments
     */
    public function getPayments()
    {
        return $this->_payments;
    }


    /**
     * @return string
     */
    public function getPaymentId()
    {
        if ($this->_payments == null) {
            $this->_initPaymentByOrder();
        }

        return $this->getPayments()->getPaymentId();
    }


    /**
     * @return string
     */
    public function getTransactionId()
    {
        if ($this->_payments == null) {
            $this->_initPaymentByOrder();
        }

        return $this->getPayments()->getTransactionId();
    }


    /**
     * @return bool
     */
    public function canRetryPayment()
    {
        try {
            /**
             * Check is the allowed routes.
             *
             * @var Mage_Core_Controller_Front_Action $controller
             */
            $controller = Mage::app()->getFrontController()->getAction();
            $actionName = $controller->getFullActionName('/');

            $allowedActions = array(
                'sales/order/view',
                'redepay/checkout/state'
            );

            if (!in_array($actionName, $allowedActions)) {
                return (bool) false;
            }

//            $enabled  = (bool) Mage::getStoreConfig('payment/rede_pay/payment_retries_enabled');
//            $maxTimes = (int)  Mage::getStoreConfig('payment/rede_pay/max_payment_retries');

//            if (!$enabled) {
//                return (bool) false;
//            }

            /** @var Mage_Sales_Model_Order $order */
            $order = $this->getInfo()->getOrder();
            if (!$order || !$order->getId()) {
                return (bool) false;
            }

            /** @var Rede_Pay_Model_Payments $payments */
//            $payments = Mage::getModel('rede_pay/payments')->loadByOrderId($order->getId());

//            if ($payments->getPaymentRetries() >= $maxTimes) {
//                return (bool) false;
//            }

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
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->getInfo()->getOrder();
    }


    /**
     * @param Mage_Sales_Model_Order|null $order
     *
     * @return $this
     */
    protected function _initPaymentByOrder(Mage_Sales_Model_Order $order = null)
    {
        if (empty($order)) {
            $order = $this->getInfo()->getOrder();
        }

        if ($this->_payments) {
            return $this;
        }

        $this->_payments = Mage::getModel('rede_pay/payments');
        $this->_payments->loadByOrderId($order->getId());

        return $this;
    }

}
