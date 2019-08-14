<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_NotificationController
 */
class Rede_Pay_NotificationController extends Rede_Pay_Controller_Front_Action
{


    /**
     * Notification action.
     */
    public function indexAction()
    {
        if (!$this->_validateRequest()) {
            $this->_redirect('');
            return;
        }

        $token     = $this->getRequest()->getParam('token');
        $reference = $this->getRequest()->getParam('reference');
        $status    = $this->getRequest()->getParam('status');
        $amount    = $this->getRequest()->getParam('amount');
        $paymentId = $this->getRequest()->getParam('orderId');

        $this->_logger()->notificationLog($this->getRequest()->getParams());

        if (!$paymentId || !$status || !$reference || !$token) {
            $this->_redirect('');
            return;
        }

        $this->_logger()->setFilename('Rede_Pay_Notification_Params');
        $this->_logger()->startTransactionLog($reference);
        $this->_logger()->log($this->getRequest()->getParams());
        $this->_logger()->finishTransactionLog($reference);

        $configToken = $this->_helper()->getConfigNotificationToken();
        if (($token != $configToken) || !$this->getApi()->isStatusAllowed($status)) {
            $this->_redirect('');
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->loadByIncrementId(trim($reference));

        if (!$reference || !$order->getId()) {
            $this->_redirect('');
            return;
        }

        /**
         * Let's make sure that the Payment ID is a valid one.
         *
         * @var Rede_Pay_Model_Payments $payments
         */
        $payments = Mage::getModel('rede_pay/payments')->loadByOrderId($order->getId());

        if ($paymentId !== $payments->getPaymentId()) {
            $this->_redirect('');
            return;
        }

        $this->getApi()->consultOrder($payments->getPaymentId(), $order);

        $body = (array) $this->getApi()->getBody();

        $transactionId         = $this->_getFilteredTransactionId($body);
        $body['transactionId'] = $transactionId;
        $status                = $body['status'];

        $payments->setTransactionId($transactionId)
            ->setAdditionalInformation($body)
            ->save();

        $processType = Rede_Pay_Model_Processor_Order::PROCESS_TYPE_NOTIFICATION;

        $this->_helper()->processOrderConsultStatus($order, $status, $body, $processType);
    }


    /**
     * Redirect action.
     */
    public function redirectAction()
    {
        if (!$this->_validateRequest()) {
            $this->_redirect('*/checkout/error');
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $this->_initOrder();

        if ($order === false) {
            return;
        }

        if (!$order || !$order->getId()) {
            $this->_redirect('*/checkout/error');
            return;
        }

        /** @var Rede_Pay_Model_Payments $payments */
        $payments  = Mage::getModel('rede_pay/payments')->loadByOrderId($order->getId());
        $paymentId = $payments->getPaymentId();

        if (empty($paymentId)) {
            $this->_redirectByOrder($order);
            return;
        }

        $redirectParams = array(
            'order_id' => $order->getId(),
        );

        if ($order->isCanceled() || ($payments->getStatus() == Rede_Pay_Model_Api::STATUS_DENIED)) {
            $this->_redirect('*/checkout/denied', $redirectParams);
            return;
        }

        $this->getApi()->consultOrder($paymentId, $order);

        $body = (array) $this->getApi()->getBody();

        /**
         * Checks the status in the response.
         *
         * 200: OK
         * 201: Created
         * 204: No Content
         * 304: Not Modified
         * 400: Bad Request
         * 401: Unauthorized
         * 403: Forbidden
         * 404: Not Found
         * 405: Method Not Allowed
         * 409: Conflict
         * 500: Internal Server Error
         */
        if ($this->getApi()->getResult()->getStatus() !== 200) {
            $this->_redirectByOrder($order);
            return;
        }

        if (!isset($body['orderId'], $body['reference'], $body['status'])) {
            $this->_redirectByOrder($order);
            return;
        }

        $reference = $body['reference'];

        if ($reference != $order->getRealOrderId()) {
            $this->_redirectByOrder($order);
            return;
        }

        $transactionId         = $this->_getFilteredTransactionId($body);
        $body['transactionId'] = $transactionId;

        /**
         * Proceed to status verification.
         */
        $status   = isset($body['status']) ? $body['status'] : null;
        $redirect = $this->_helper()->processOrderConsultStatus($order, $status, $body);

        if ($redirect === Rede_Pay_Model_Processor_Order::REDIRECT_SUCCESS) {
            $this->_redirect('*/checkout/success', $redirectParams);
            return;
        }

        if ($redirect === Rede_Pay_Model_Processor_Order::REDIRECT_ERROR) {
            $this->_redirect('*/checkout/error', $redirectParams);
            return;
        }

        if ($redirect === Rede_Pay_Model_Processor_Order::REDIRECT_DENIED) {
            $this->_redirect('*/checkout/denied', $redirectParams);
            return;
        }

        if ($redirect === Rede_Pay_Model_Processor_Order::REDIRECT_STATE) {
            $this->_redirectByOrder($order);
            return;
        }

        $this->_redirectByOrder($order);
    }


    /**
     * Cancel action.
     */
    public function cancelAction()
    {
        if (!$this->_validateRequest()) {
            $this->_redirect('*/checkout/error');
            return;
        }

        /**
         * This method only occurs when customer is not logged in.
         */
        if (!$this->_isCustomerLoggedIn()) {
            $this->_redirect('customer/account/login');
            return;
        }

        $orderId = $this->getRequest()->getParam('order_id');

        if (empty($orderId)) {
            $orderId = $this->_getSession()->getLastOrderId();
        }

        /** @var Rede_Pay_Model_Payments $payments */
        $payments  = Mage::getModel('rede_pay/payments')->loadByOrderId($orderId);
        $paymentId = $payments->getPaymentId();

        if (empty($paymentId)) {
            $paymentId = $this->_getSession()->getLastPaymentId();
        }

        if (!$paymentId || !$orderId) {
            $this->_getCheckoutSession()->addError($this->__('Incorrect transaction reference.'));
            $this->_redirectCart();
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);

        if (!$order->getId()) {
            $this->_getCheckoutSession()->addError($this->__('Order ID does not exist anymore.'));
            $this->_redirectCart();
            return;
        }

        /**
         * The logged in customer needs to be the owner of the order in order to cancel it.
         */
        if ($order->getCustomerId() != $this->_getCustomerId()) {
            $this->_getCheckoutSession()->addError($this->__('You cannot cancel this order.'));
            $this->_redirectCart();
            return;
        }

        $this->_logger()->startTransactionLog($order->getRealOrderId());

        $proceedWithCancellation = true;

        try {
            $this->getApi()->consultOrder($paymentId, $order);
            $body = (array) $this->getApi()->getBody();

            $payments->setAdditionalInformation($body)->save();

            /**
             * When order status is OPEN on Rede Pay the customer is able to try a new payment
             *   so we can't cancel the order.
             */
            if (empty($body) || (!empty($body['status']) && $this->getApi()->isStatusOpen($body['status']))) {
                $proceedWithCancellation = false;
            }

            /**
             * If order is not canceled.
             */
            if ((true === $proceedWithCancellation) && $order->canCancel()) {
                $order->cancel()->save();

                /** @var Mage_Sales_Model_Order_Status_History $history */
                $history = $order->addStatusHistoryComment($this->__('Order canceled by Rede Pay.'));
                $history->save();

                $message = $this->__('Order #%s is canceled.', $order->getRealOrderId());
                $this->_logger()->log($message, false);
            }
        } catch (Exception $e) {
            $this->_getCheckoutSession()->addError($this->__('Some error occurred when trying to cancel the order.'));
            Mage::logException($e);
            $this->_logger()->log($e->getMessage(), false);
            $this->_redirectCart();
            return;
        }

        $this->_logger()->finishTransactionLog($order->getRealOrderId());

        $params = array('order_id' => $order->getId());

        if ($this->getApi()->isStatusCancelled($payments->getStatus())) {
            $this->_redirect('*/checkout/denied', $params);
            return;
        }

        if (false === $proceedWithCancellation) {
            $this->_redirect('*/checkout/state', $params);
            return;
        }

        $this->_redirect('*/checkout/error', $params);
    }


    /**
     * Add Tracking from Rede Pay
     */
    public function addTrackingAction()
    {
        if (!$this->_validateRequest()) {
            $this->_redirect('*/checkout/error');
            return;
        }

        $transactionId  = $this->getRequest()->getParam('transactionId');
        $paymentId      = $this->getRequest()->getParam('orderId');
        $orderId        = $this->getRequest()->getParam('reference');
        $trackingNumber = $this->getRequest()->getParam('trackingNumber');

        if (!$transactionId || !$paymentId || !$orderId || !$trackingNumber) {
            return;
        }

        /** @var Rede_Pay_Model_Validator_Shipping_Tracking $validator */
        $validator = Mage::getModel('rede_pay/validator_shipping_tracking');

        if (!$validator->isValid($trackingNumber)) {
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        if (!$order->getId()) {
            return;
        }

        /** @var Rede_Pay_Model_Payments $payment */
        $payment = Mage::getModel('rede_pay/payments')->loadByOrderId($order->getId());

        if ($payment->getPaymentId() != $paymentId) {
            return;
        }

        $payment->setTransactionId($transactionId)->save();

        /**
         * @var Mage_Sales_Model_Resource_Order_Shipment_Collection $shipments
         * @var Mage_Sales_Model_Order_Shipment                     $shipment
         */
        $shipments = $order->getShipmentsCollection();
        $shipment  = null;

        if ($shipments->getSize()) {
            $shipment = $shipments->getFirstItem();

            $message = $this->__(
                'Tracking number %s added to order shipment by Rede Pay notification process.', $trackingNumber
            );
        } else {
            if (!$order->canShip()) {
                /** Order cannot be shipped. */
                return;
            }

            $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment();
            $shipment->register();

            $message = $this->__(
                'Order shipment created by Rede Pay notification process with tracking number %s.', $trackingNumber
            );
        }

        if (!($shipment instanceof Mage_Sales_Model_Order_Shipment)) {
            return;
        }

        /** @var Mage_Sales_Model_Order_Shipment_Track $shipmentTracks */
        foreach ($shipment->getAllTracks() as $shipmentTracks) {
            /**
             * If tracking already exists, there's nothing to do.
             */
            if ($shipmentTracks->getNumber() == $trackingNumber) {
                return;
            }
        }

        /** @var Mage_Sales_Model_Order_Shipment_Track $track */
        $track = Mage::getModel('sales/order_shipment_track')->setTitle('Correios')
            ->setCarrierCode('custom')
            ->setNumber($trackingNumber);

        $order->setIsInProcess(true);
        $shipment->addTrack($track)->addComment($message);

        /** @var Mage_Sales_Model_Order_Status_History $history */
        $history = $order->addStatusHistoryComment($message, true);

        $this->getTransaction()
            ->addObject($order)
            ->addObject($shipment)
            ->addObject($history)
            ->save();
    }


    /**
     * Remove Tracking from Rede Pay
     */
    public function removeTrackingAction()
    {
        if (!$this->_validateRequest()) {
            $this->_redirect('*/checkout/error');
            return;
        }

        $transactionId  = $this->getRequest()->getParam('transactionId');
        $paymentId      = $this->getRequest()->getParam('orderId');
        $orderId        = $this->getRequest()->getParam('reference');
        $trackingNumber = $this->getRequest()->getParam('trackingNumber');

        if (!$transactionId || !$paymentId || !$orderId || !$trackingNumber) {
            return;
        }

        /** @var Rede_Pay_Model_Validator_Shipping_Tracking $validator */
        $validator = Mage::getModel('rede_pay/validator_shipping_tracking');

        if (!$validator->isValid($trackingNumber)) {
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        if (!$order->getId()) {
            return;
        }

        /** @var Rede_Pay_Model_Payments $payment */
        $payment = Mage::getModel('rede_pay/payments')->loadByOrderId($order->getId());

        if ($payment->getPaymentId() != $paymentId) {
            return;
        }

        $payment->setTransactionId($transactionId)->save();

        /** @var Mage_Sales_Model_Resource_Order_Shipment_Collection $shipments */
        $shipments = $order->getShipmentsCollection();

        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        foreach ($shipments as $shipment) {
            /** @var Mage_Sales_Model_Order_Shipment_Track $shipmentTracks */
            foreach ($shipment->getAllTracks() as $shipmentTracks) {
                /**
                 * If tracking already exists, there's nothing to do.
                 */
                if ($shipmentTracks->getNumber() == $trackingNumber) {
                    $shipmentTracks->delete();

                    $message = $this->__(
                        'Tracking number %s removed by Rede Pay notification process.', $trackingNumber
                    );

                    $shipment->addComment($message);
                    $history = $order->addStatusHistoryComment($message, true);

                    $this->getTransaction()
                        ->addObject($shipment)
                        ->addObject($history)
                        ->save();
                }
            }
        }
    }


    /**
     * @return Mage_Core_Model_Resource_Transaction
     */
    protected function getTransaction()
    {
        return Mage::getResourceModel('core/transaction');
    }

}
