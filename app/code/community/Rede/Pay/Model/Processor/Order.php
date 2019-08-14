<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Processor_Order
 */
class Rede_Pay_Model_Processor_Order
{

    use Rede_Pay_Trait_Data;

    const STATUS_ERROR              = 'error';

    const PROCESS_TYPE_CONSULT      = 'consult';
    const PROCESS_TYPE_NOTIFICATION = 'notification';

    const REDIRECT_SUCCESS          = 'success';
    const REDIRECT_ERROR            = 'error';
    const REDIRECT_REVERSED         = 'refunded';
    const REDIRECT_DENIED           = 'denied';
    const REDIRECT_STATE            = 'state';

    /**
     * @param Mage_Sales_Model_Order $order
     * @param array                  $result
     *
     * @return string
     */
    public function processOrderConsultStatus(Mage_Sales_Model_Order $order, $status = null, $result = array(),
                                              $processType = null)
    {
        $paymentId     = isset($result['orderId'])       ? $result['orderId']       : null;
        $transactionId = isset($result['transactionId']) ? $result['transactionId'] : null;

        if ($result && $paymentId && $order->getId()) {
            /** @var Rede_Pay_Model_Payments $payments */
            $payments = Mage::getModel('rede_pay/payments')->loadByOrderId($order->getId());
            $payments->setAdditionalInformation($result);

            if ($transactionId) {
                $payments->setTransactionId($transactionId);
            }

            $payments->save();
        }

        if (empty($processType)) {
            $processType = self::PROCESS_TYPE_CONSULT;
        }

        if (!empty($status)) {
            return $this->processOrderStatus($order, $status, $transactionId, $processType);
        }

        return self::REDIRECT_STATE;
    }


    /**
     * @param Mage_Sales_Model_Order $order
     * @param string                 $status
     *
     * @return string
     *
     * @throws Exception
     */
    public function processOrderStatus(Mage_Sales_Model_Order $order, $status, $transactionId = null,
                                       $processType = null)
    {
        if (empty($status)) {
            /** @var Mage_Sales_Model_Order_Status_History $history */
            $message = $this->_helper()->__('Status sent is empty and will bot be processed.');
            $history = $order->addStatusHistoryComment($message);

            $history->save();

            return self::REDIRECT_STATE;
        }

        $processLabel = $this->_getProcessTypeLabel($processType);

        /** @var Mage_Sales_Model_Order_Status_History $history */
        $history = $order->addStatusHistoryComment(
            $this->_helper()->__('Order Status Change by %s Process from Rede Pay: %s.', $processLabel, $status)
        );

        $history->save();

        $result = null;

        /**
         * If order is held.
         */
        if ($order->canUnhold()) {
            $order->unhold();
        }

        try {
            switch ($status) {
                case Rede_Pay_Model_Api::STATUS_APPROVED:
                case Rede_Pay_Model_Api::STATUS_COMPLETED:
                    if (!$order->canInvoice()) {
                        return self::REDIRECT_STATE;
                    }

                    /** @var Mage_Sales_Model_Order_Invoice $invoice */
                    $invoice = $this->_initInvoice($order);
                    $message = $this->_helper()->__('Order Invoiced By %s Process From Rede Pay.', $processLabel);

                    if ($invoice) {
                        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE)
                            ->setTransactionId($transactionId)
                            ->addComment($message)
                            ->register()
                            ->sendEmail(true);
                    }

                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $message)
                        ->setIsInProcess(true);

                    $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($order);

                    $transactionSave->save();

                    $result = self::REDIRECT_SUCCESS;

                    break;
                case Rede_Pay_Model_Api::STATUS_REVERSED:
                    /**
                     * Let's check if payment is first in analysis but cancelled afterwards by Rede then we need
                     * to cancel the oder.
                     */
                    if (!$order->hasInvoices() && $order->canCancel()) {
                        $message = $this->_helper()->__(
                            'Payment was REVERSED but no invoices was created. %s Process From Rede Pay.',
                            $processLabel
                        );

                        $order->getPayment()->cancel();
                        $order->registerCancellation($message);

                        Mage::dispatchEvent('order_cancel_after', array('order' => $this));

                        $order->save();

                        $result = self::REDIRECT_REVERSED;
                        break;
                    }

                    /**
                     * If order has invoices but don't allow credit memo we can do nothing.
                     */
                    if (!$order->canCreditmemo()) {
                        /** @var Mage_Sales_Model_Order_Status_History $history */
                        $history = $order->addStatusHistoryComment($this->_helper()->__(
                            'Order status is %s but credit memo cannot be created.', Rede_Pay_Model_Api::STATUS_REVERSED
                        ));

                        $history->save();

                        $result = self::REDIRECT_REVERSED;
                        break;
                    }

                    /**
                     * If order has invoices then we can proceed with credit memo.
                     */
                    $message = $this->_helper()->__('Order Refunded By %s Process From Rede Pay.', $processLabel);

                    /** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $invoiceCollection */
                    $invoiceCollection = $order->getInvoiceCollection();

                    /** @var Mage_Sales_Model_Service_Order $service */
                    $service     = Mage::getModel('sales/service_order', $order);

                    /** @var Mage_Core_Model_Resource_Transaction $transaction */
                    $transaction = Mage::getModel('core/resource_transaction');

                    foreach ($invoiceCollection as $invoice) {
                        $invoice->addComment($message);

                        /** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
                        $creditmemo = $service->prepareInvoiceCreditmemo($invoice);
                        $creditmemo->setOfflineRequested(true);
                        $creditmemo->setTransactionId($invoice->getTransactionId());
                        $creditmemo->addComment($message);
                        $creditmemo->register();

                        foreach ($creditmemo->getAllItems() as $creditmemoItems) {
                            /**
                             * @var  $creditmemoItems
                             * @var  $orderItem
                             */
                            $orderItem = $creditmemoItems->getOrderItem();

                            if (!$orderItem->getParentItemId()) {
                                $orderItem->setBackToStock(true);
                            }
                        }

                        $transaction->addObject($invoice);
                        $transaction->addObject($creditmemo);
                    }

                    /** @var Mage_Sales_Model_Order_Status_History $history */
                    $history = $order->addStatusHistoryComment($message);

                    $transaction->addObject($history);
                    $transaction->addObject($order);
                    $transaction->save();

                    $result = self::REDIRECT_REVERSED;

                    break;

                case Rede_Pay_Model_Api::STATUS_DENIED:
                case Rede_Pay_Model_Api::STATUS_CANCELLED:
                    if (!$order->canCancel()) {
                        return self::REDIRECT_DENIED;
                    }

                    $message = $this->_helper()->__('Order Canceled By %s Process From Rede Pay.', $processLabel);

                    $order->getPayment()->cancel();
                    $order->registerCancellation($message);

                    Mage::dispatchEvent('order_cancel_after', array('order' => $this));

                    $order->save();

                    $result = self::REDIRECT_DENIED;

                    break;
                case Rede_Pay_Model_Api::STATUS_IN_ANALYSIS:
                    if ($order->canHold()) {
                        $order->hold();
                    }

                    $message = $this->_helper()
                        ->__('Order Is IN ANALYSIS By %s Process From Rede Pay.', $processLabel);

                    $history = $order->addStatusHistoryComment($message, true);

                    /** @var Mage_Core_Model_Resource_Transaction $transaction */
                    $transaction = Mage::getModel('core/resource_transaction');
                    $transaction->addObject($order)
                        ->addObject($history)
                        ->save();

                    $result = self::REDIRECT_STATE;

                    break;
                case Rede_Pay_Model_Api::STATUS_OPEN:
                case Rede_Pay_Model_Api::STATUS_IN_DISPUTE:
                case Rede_Pay_Model_Api::STATUS_CHARGEBACK:

                    $result = self::REDIRECT_STATE;

                    break;
                case self::STATUS_ERROR:
                default:
                    if (!$order->canCancel()) {
                        $result = self::REDIRECT_STATE;
                        break;
                    }

                    $message = $this->_helper()->__('Canceling order. Error when trying to process the order payment.');
                    $order->getPayment()->cancel();
                    $order->registerCancellation($message);

                    Mage::dispatchEvent('order_cancel_after', array('order' => $this));

                    $order->save();

                    $result = self::REDIRECT_ERROR;
                    break;
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $result = self::REDIRECT_STATE;
        }

        return $result;
    }


    /**
     * @param string $processType
     *
     * @return null
     */
    protected function _getProcessTypeLabel($processType)
    {
        $typeLabels = array(
            self::PROCESS_TYPE_NOTIFICATION => $this->_helper()->__('Notification'),
            self::PROCESS_TYPE_CONSULT      => $this->_helper()->__('Consult'),
        );

        return isset($typeLabels[$processType]) ? $typeLabels[$processType] : null;
    }


    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return bool|Mage_Sales_Model_Order_Invoice
     *
     * @throws Mage_Core_Exception
     */
    protected function _initInvoice(Mage_Sales_Model_Order $order)
    {
        /**
         * Check order existing
         */
        if (!$order->getId()) {
            return false;
        }

        /**
         * Check invoice create availability
         */
        if (!$order->canInvoice()) {
            return false;
        }

        $qtys = array();

        foreach ($order->getAllItems() as $item) {
            /** @var Mage_Sales_Model_Order_Item $item */
            $qtys[$item->getId()] = $item->getQtyOrdered();
        }

        /** @var Mage_Sales_Model_Order_Invoice $invoice */
        $invoice = $order->prepareInvoice($qtys);

        if (!$invoice->getTotalQty()) {
            Mage::throwException($this->_helper()->__('Cannot create an invoice without products.'));
        }

        Mage::register('current_invoice', $invoice);
        return $invoice;
    }

}
