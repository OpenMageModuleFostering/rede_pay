<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_CheckoutController
 */
class Rede_Pay_CheckoutController extends Rede_Pay_Controller_Front_Action
{

    /**
     * Success action.
     */
    public function successAction()
    {
        $this->_initLayout();

        $orderId = $this->_initOrderId();
        if (false === $orderId) {
            return;
        }

        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($orderId)));

        $this->_renderLayout();
        $this->_clearSessions();
    }


    /**
     * Pending action.
     */
    public function pendingAction()
    {
        $this->_common(true);
    }


    /**
     * Pending action.
     */
    public function stateAction()
    {
        $this->_common(true);
    }


    /**
     * Error action.
     */
    public function errorAction()
    {
        $this->_common(true);
    }


    /**
     * Error action.
     */
    public function deniedAction()
    {
        $this->_common(true);
    }


    /**
     * Payment Action
     */
    public function paymentAction()
    {
        $this->_common();
    }


    public function retryAction()
    {
        if (!$this->getRequest()->isPost()) {
            return;
        }

        if (!$this->_isCustomerLoggedIn()) {
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->load($this->getRequest()->getPost('order_id', null));

        if (!$order->getId()) {
            return;
        }

        if (($order->getCustomerId() != $this->_getCustomerId())) {
            return;
        }

        /** @var Rede_Pay_Model_Payments $payments */
        $payments = Mage::getModel('rede_pay/payments');
        $payments->loadByOrderId($order->getId());

        if (!$payments->getId()) {
            return;
        }

        $payments->incrementRetry()->save();

        $result = array(
            'success' => true,
            'retries' => $payments->getPaymentRetries(),
        );

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(Zend_Json_Encoder::encode($result));
    }

}
