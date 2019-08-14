<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Controller_Front_Action
 */
class Rede_Pay_Controller_Front_Action extends Mage_Sales_Controller_Abstract
{

    use Rede_Pay_Trait_Data;
    

    /**
     * @param bool|null|string $handles
     * @param bool             $generateBlocks
     * @param bool             $generateXml
     *
     * @return $this
     */
    protected function _initLayout($handles = null, $generateBlocks = true, $generateXml = true)
    {
        $this->loadLayout($handles, $generateBlocks, $generateXml);
        $this->_title('Rede Pay');

        return $this;
    }


    /**
     * @param string $output
     *
     * @return $this
     */
    protected function _renderLayout($output = '')
    {
        $this->renderLayout($output);
        return $this;
    }


    /**
     * @return Mage_Sales_Model_Order
     */
    protected function _initOrder($orderId = null)
    {
        if (empty($orderId)) {
            $orderId = $this->getRequest()->getParam('order_id');
        }

        if (empty($orderId)) {
            $orderId = $this->_getCheckoutSession()->getLastOrderId();
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

        Mage::register('current_order', $order, true);

        return $order;
    }


    /**
     * @return bool
     */
    protected function _validateRequest($autoRedirect = true)
    {
        $result = true;

        if (($autoRedirect == true) && ($result == false)) {
            $this->_redirect('');
        }

        return (bool) $result;
    }
    

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return $this
     */
    protected function _redirectByOrder(Mage_Sales_Model_Order $order)
    {
        if (!$order->getId()) {
            $this->_redirectCart();
            return $this;
        }

        if ($order->isCanceled()) {
            $this->_redirectPaymentError($order);
            return $this;
        }

        switch ($order->getState()) {
            case Mage_Sales_Model_Order::STATE_NEW:
                $this->_redirectOrderPending($order);
                break;
            case Mage_Sales_Model_Order::STATE_CANCELED:
                $this->_redirectPaymentError($order);
                break;
            case Mage_Sales_Model_Order::STATE_PROCESSING:
            case Mage_Sales_Model_Order::STATE_COMPLETE:
            case Mage_Sales_Model_Order::STATE_CLOSED:
            case Mage_Sales_Model_Order::STATE_HOLDED:
            case Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW:
            default:
                $this->_redirectOrderState($order);
                break;
        }

        return $this;
    }


    /**
     * @return $this
     */
    protected function _redirectOrderState(Mage_Sales_Model_Order $order)
    {
        $this->_redirect('*/checkout/state', array('order_id' => $order->getId()));
        return $this;
    }


    /**
     * @return $this
     */
    protected function _redirectOrderPending(Mage_Sales_Model_Order $order)
    {
        $this->_redirect('*/checkout/pending', array('order_id' => $order->getId()));
        return $this;
    }


    /**
     * @return $this
     */
    protected function _redirectPaymentError(Mage_Sales_Model_Order $order)
    {
        $this->_redirect('*/checkout/error', array('order_id' => $order->getId()));
        return $this;
    }


    /**
     * @return $this
     */
    protected function _redirectCart()
    {
        $this->_redirect('checkout/cart');
        return $this;
    }


    /**
     * @return $this
     */
    protected function _clearSessions()
    {
        $this->_getSession()->clear();
        $this->_getSession()->unsetData('last_transaction_info');

        $this->_getCheckoutSession()->clear();
        $this->_getCheckoutSession()->unsetData('last_order_id');
        $this->_getCheckoutSession()->unsetData('last_quote_id');

        return $this;
    }


    /**
     * @return bool
     */
    protected function _isCustomerLoggedIn()
    {
        return $this->_getCustomerSession()->isLoggedIn();
    }


    /**
     * @return int|null
     */
    protected function _getCustomerId()
    {
        return $this->_getCustomerSession()->getCustomerId();
    }


    /**
     * @return Mage_Customer_Model_Session
     */
    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }


    /**
     * Action used in most of the methods above.
     */
    protected function _common($clearSession = false)
    {
        $order = $this->_initOrder();

        if (false === $order) {
            return;
        }

        $this->_initLayout();
        $this->_renderLayout();

        if (true === $clearSession) {
            $this->_clearSessions();
        }
    }


    /**
     * @return bool|int|mixed
     */
    protected function _initOrderId()
    {
        $orderId = $this->getRequest()->getParam('order_id', null);

        if (empty($orderId)) {
            $orderId = $this->_getLastOrderId();
        }

        if (empty($orderId)) {
            $this->_redirectCart();
            return false;
        }

        return $orderId;
    }

}
