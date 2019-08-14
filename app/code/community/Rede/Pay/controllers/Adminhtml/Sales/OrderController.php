<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Adminhtml_Sales_OrderController
 */
class Rede_Pay_Adminhtml_Sales_OrderController extends Rede_Pay_Controller_Adminhtml_Action
{

    public function consultOrderAction()
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $this->_initOrder();

        if (!$order->getId()) {
            $this->_getAdminSession()->addError($this->__('Order was not found.'));
            $this->_redirectReferer();
            return;
        }

        /** @var Rede_Pay_Model_Payments $payments */
        $payments = Mage::getModel('rede_pay/payments')->loadByOrderId($order->getId());

        if (empty($payments->getPaymentId())) {
            $this->_getAdminSession()->addError($this->__('This order was not found in Rede Pay.'));
            $this->_redirectReferer();
            return;
        }

        /** @var Rede_Pay_Model_Processor $processor */
        $this->_processor()->setOrder($order);

        $this->getApi()->consultOrder($payments->getPaymentId());

        $body = (array) $this->getApi()->getBody();

        if (!empty($body) && isset($body['status']) && $this->getApi()->isStatusAllowed($body['status'])) {
            $status = $body['status'];

            $transactionId         = $this->_getFilteredTransactionId($body);
            $body['transactionId'] = $transactionId;

            $this->_helper()->processOrderConsultStatus($order, $status, $body);
            $this->_getAdminSession()->addSuccess($this->__('Order was found and update successfully.'));
        } elseif ($this->getApi()->getResult()->getStatus() === 404) {
            $this->_getAdminSession()->addError($this->__('This order was not found in the Gateway.'));
        } else {
            $this->_getAdminSession()
                 ->addError($this->__('Some error has occurred when trying to consult this order.'));
        }

        $this->_redirect('*/*/view', array('order_id' => $order->getId()));
    }

}
