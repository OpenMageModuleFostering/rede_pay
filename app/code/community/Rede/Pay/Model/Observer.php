<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Observer
 */
class Rede_Pay_Model_Observer
{

    use Rede_Pay_Trait_Data;

    /**
     * @param Varien_Event_Observer $observer
     */
    public function appendPaymentsToOrderCollection(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = $observer->getData('order_collection');

        if (!$collection) {
            $collection = $observer->getData('order_grid_collection');
        }

        if (!$collection || !($collection instanceof Mage_Sales_Model_Resource_Order_Collection)) {
            return;
        }

        Mage::getResourceModel('rede_pay/payments')->appendPaymentInfoToOrderCollection($collection);
    }


    /**
     * @param Varien_Event_Observer $observer
     */
    public function appendPaymentsToOrder(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getData('order');

        if (!$order) {
            return;
        }

        Mage::getResourceModel('rede_pay/payments')->appendPaymentInfoToOrder($order);
    }


    /**
     * @param Varien_Event_Observer $observer
     */
    public function modifySalesOrderGrid(Varien_Event_Observer $observer)
    {
        /** @var Mage_Adminhtml_Block_Sales_Order_Grid $block */
        $block = $observer->getData('block');

        if (!($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid)) {
            return;
        }

        $block->addColumnAfter('rede_payment_id', array(
            'header'   => Mage::helper('rede_pay')->__('Rede Payment ID'),
            'index'    => 'rede_payment_id',
            'type'     => 'text',
            'filter'   => false,
            'sortable' => false,
            'width'    => '250px',
        ), 'real_order_id');

        $block->sortColumnsByOrder();
    }


    /**
     * @param Varien_Event_Observer $observer
     */
    public function addOrderConsultButton(Varien_Event_Observer $observer)
    {
        /** @var Mage_Adminhtml_Block_Sales_Order_View $block */
        $block = $observer->getData('block');

        if (!($block instanceof Mage_Adminhtml_Block_Sales_Order_View)) {
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::registry('current_order');

        if (empty($order)) {
            $orderId = Mage::app()->getRequest()->getParam('order_id');
            $order   = Mage::getModel('sales/order')->load($orderId);
        }

        $statuses = array(
            $order::STATE_CANCELED,
            $order::STATE_CLOSED,
            $order::STATE_COMPLETE,
        );

        if (!$order->canCancel() && !$order->canCreditmemo() || (in_array($order->getState(), $statuses))) {
            return;
        }

        $params  = array('order_id' => $order->getId());
        $url     = Mage::helper('adminhtml')->getUrl('*/*/consultOrder', $params);

        $block->addButton('consult_button', array(
            'label'     => $this->_helper()->__('Rede Pay Consult'),
            'onclick'   => 'setLocation(\'' . $url .'\')',
            'class'     => 'go',
        ), 0, 100);
    }


    /**
     * If the tracking code is valid then we need to treat it before saving it to shipment.
     *
     * @param Varien_Event_Observer $observer
     */
    public function prepareShipmentTrack(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Shipment_Track $track */
        $track  = $observer->getData('track');
        $number = strtoupper(trim($track->getNumber()));

        /** @var Rede_Pay_Model_Validator_Shipping_Tracking $validator */
        $validator = Mage::getModel('rede_pay/validator_shipping_tracking');

        if (true === $validator->isValid($number)) {
            $track->setNumber($number);
        }

        if ($track->isObjectNew()) {
            $track->setData('integrate_candidate', true);
        }
    }


    /**
     * @param Varien_Event_Observer $observer
     */
    public function deleteShipmentTrack(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Shipment_Track $track */
        $track  = $observer->getData('track');
        $number = strtoupper(trim($track->getNumber()));

        /** @var Rede_Pay_Model_Validator_Shipping_Tracking $validator */
        $validator = Mage::getModel('rede_pay/validator_shipping_tracking');

        if (!$validator->isValid($number)) {
            return;
        }

        /** @var Rede_Pay_Model_Payments $payment */
        $payment = Mage::getModel('rede_pay/payments')->loadByOrderId($track->getOrderId());

        if (!$payment->getId() || !$payment->getTransactionId()) {
            return;
        }

        /** @var Rede_Pay_Model_Api $api */
        $api = Mage::getModel('rede_pay/api');

        /** This API method returns a 200 (OK) when it's ok. */
        $api->consult($payment->getTransactionId(), $track->getShipment()->getOrder());
        $body = $api->getBody();

        if (empty($body) || empty($body['trackingNumber'])) {
            return;
        }

        $trackingNumber = (string) trim($body['trackingNumber']);

        if ($trackingNumber === $number) {
            /** This API method returns a 204 (No Content) when it's ok. */
            $api->deleteShippingTracking($payment->getTransactionId(), $track->getShipment()->getOrder());
        }
    }


    /**
     * If tracking number is valid then we need to integrate it with the payment gateway.
     *
     * @param Varien_Event_Observer $observer
     */
    public function processShipmentTrack(Varien_Event_Observer $observer)
    {
        if (Mage::registry('shipment_track_processed') === true) {
            return;
        }

        /** @var Mage_Sales_Model_Order_Shipment_Track $track */
        $track  = $observer->getData('track');
        $number = strtoupper(trim($track->getNumber()));

        if (true !== $track->getData('integrate_candidate')) {
            return;
        }

        /** @var Rede_Pay_Model_Validator_Shipping_Tracking $validator */
        $validator = Mage::getModel('rede_pay/validator_shipping_tracking');

        if (!$validator->isValid($number)) {
            return;
        }

        /** @var Rede_Pay_Model_Payments $payment */
        $payment = Mage::getModel('rede_pay/payments')->loadByOrderId($track->getOrderId());

        if (!$payment->getId() || !$payment->getTransactionId()) {
            return;
        }

        /** @var Rede_Pay_Model_Api $api */
        $api = Mage::getModel('rede_pay/api');

        $api->consult($payment->getTransactionId(), $track->getShipment()->getOrder());
        $body = (array) $api->getBody();

        if (!empty($body) && !empty($body['trackingNumber'])) {
            /** This API method returns a 204 (No Content) when it's ok. */
            $api->deleteShippingTracking($payment->getTransactionId(), $track->getShipment()->getOrder());
        }

        /** This API method returns a 201 (Created) when it's ok. */
        $api->addShippingTracking($number, $payment->getTransactionId(), $track->getShipment()->getOrder());
        Mage::register('shipment_track_processed', true);
    }


    /**
     * @return Mage_Admin_Model_Session
     */
    protected function getAdminhtmlSession()
    {
        return Mage::getSingleton('admin/session');
    }

}
