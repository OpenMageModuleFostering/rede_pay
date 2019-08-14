<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Method_Standard
 */
class Rede_Pay_Model_Method_Standard extends Mage_Payment_Model_Method_Abstract
{

    use Rede_Pay_Trait_Data;

    protected $_code          = 'rede_pay';
    protected $_formBlockType = 'rede_pay/form_method_standard';
    protected $_infoBlockType = 'rede_pay/info_method_standard';

    /**
     * Availability options
     */
    protected $_isGateway               = true;
    protected $_canOrder                = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canUseInternal          = false;


    /**
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('redepay/checkout/payment');
    }


    /**
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return $this
     */
    public function order(Varien_Object $payment, $amount)
    {
        parent::order($payment, $amount);

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $order = $payment->getOrder();

        /** @var Rede_Pay_Model_Processor $processor */
        $processor = $this->_processor();
        $processor->setOrder($order);

        $validation = $this->_validateOrderParameters($processor->getOrderParams());
        if ($validation !== true) {
            Mage::throwException($validation);
        }

        $this->getApi()->request();

        $body = $this->getApi()->getBody();

        if ($this->getApi()->getResult()->getStatus() !== 201) {
            $errors = array();

            if ($this->getApi()->getResult()->getStatus() === 401) {
                $errors[] = $this->_helper()->__('Fail when trying to authenticate in service.');
            }

            foreach ($body as $error) {
                $errors[] = $this->_helper()->__($error['message']);
            }

            Mage::throwException(implode("\n", $errors));
        }

        if (!isset($body['id']) || empty($body['id'])) {
            Mage::throwException($this->_helper()->__('Some error has occurred when trying to process your payment.'));
        }

        $paymentId = (string) $body['id'];

        /** @var Rede_Pay_Model_Payments $payments */
        $payments = Mage::getModel('rede_pay/payments');

        $payments->setOrderId($order->getId())
            ->setOrderIncrementId($order->getIncrementId())
            ->setPaymentId($paymentId);

        try {
            $payments->save();
            $this->_getSession()->setLastTransaction($payments);
            $this->_getCheckoutSession()->setData('payment_id', $paymentId);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_logger()->finishTransactionLog($order->getRealOrderId());
            Mage::throwException($this->_helper()->__('Some error occurred when trying to save the payment data.'));
        }

        return $this;
    }


    /**
     * Refund specified amount for payment
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float                          $amount
     *
     * @return $this
     */
    public function refund(Varien_Object $payment, $amount)
    {
        parent::refund($payment, $amount);

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $order = $payment->getOrder();

        /** @var Rede_Pay_Model_Processor $processor */
        $processor = $this->_processor();
        $processor->setOrder($order);

        $validation = $this->_validateRefundParameters($processor->getRefundParams());
        if ($validation !== true) {
            Mage::throwException($validation);
        }

        /** @var Rede_Pay_Model_Payments $payments */
        $payments      = Mage::getModel('rede_pay/payments')->loadByOrderId($order->getId());
        $transactionId = $payments->getTransactionId();

        if (!$transactionId) {
            Mage::throwException($this->_helper()->__('Transaction ID is empty for this order.'), 'adminhtml/session');
            return $this;
        }

        /** @var Rede_Pay_Model_Api $api */
        $api = Mage::getModel('rede_pay/api');
        $api->refund($transactionId);

        $this->_validateRefundResult($api);

        if (($api->getResult()->getStatus() === 202) && Mage::app()->getStore()->isAdmin()) {
            $message = $this->_helper()->__('Your order was successfully refunded.');
            $this->_getAdminSession()->addSuccess($message);
        }

        return $this;
    }


    /**
     * @param Rede_Pay_Model_Api $api
     *
     * @return $this
     */
    protected function _validateRefundResult(Rede_Pay_Model_Api $api)
    {
        /**
         * If request was not authorized in Rede Pay.
         */
        if ($api->getResult()->getStatus() === 401) {
            $message = $this->_helper()->__('This request was Unauthorized by Rede Pay.');
            Mage::throwException($message);
        }

        /**
         * If order was not found in Rede Pay.
         */
        if ($api->getResult()->getStatus() === 404) {
            $message = $this->_helper()->__('This order was not found in Rede Pay.');
            Mage::throwException($message);
        }

        /**
         * Any other error.
         */
        $status  = (int) $api->getResult()->getStatus();
        $okCodes = array(200, 201, 202, 204);
        if (!in_array($status, $okCodes) && Mage::app()->getStore()->isAdmin()) {
            $message = $this->_helper()->__('Some error has occurred when trying to refund the order.');

            if (is_array($api->getBody())) {
                foreach ($api->getBody() as $error) {
                    $this->_getAdminSession()->addError($error['message']);
                }
            }

            Mage::throwException($message);
        }

        return $this;
    }


    /**
     * @param array $parameters
     *
     * @return array|bool
     */
    protected function _validateRefundParameters($parameters = array())
    {
        $errors = array();

        /**
         * Status
         */
        if (!isset($parameters['status']) || !($parameters['status'] == Rede_Pay_Model_Api::STATUS_REVERSED)) {
            $errors[] = $this->_helper()->__('Status parameter is incorrect for refund request.');
        }

        if (!empty($errors)) {
            $errors = implode("\n", $errors);
        }

        return empty($errors) ? true : $errors;
    }


    /**
     * @param array $parameters
     *
     * @return array|bool
     */
    protected function _validateOrderParameters($parameters = array())
    {
        $errors = array();

        /**
         * Documents
         */
        if (!isset($parameters['customer']['documents'][0]['number'])) {
            $errors[] = $this->_helper()->__('Document number is necessary to proceed with order.');
        }

        $document  = $parameters['customer']['documents'][0]['number'];
        $validator = Mage::getModel('rede_pay/validator_cpf');
        if (!$validator->isValid($document)) {
            $errors[] = $this->_helper()->__('CPF number is not valid.');
        }

        /**
         * Phone numbers
         */
        if (!isset($parameters['customer']['phones']) && !count($parameters['customer']['phones'])) {
            $errors[] = $this->_helper()->__('At least cellphone is necessary to proceed with order.');
        }

        /**
         * Order items
         */
        if (!isset($parameters['items']) && !count($parameters['items'])) {
            $errors[] = $this->_helper()->__('At least one product is necessary to proceed with order.');
        }

        if (!empty($errors)) {
            $errors = implode("\n", $errors);
        }

        return empty($errors) ? true : $errors;
    }


    /**
     * Do not validate payment form using server methods
     *
     * @return  bool
     */
    public function validate()
    {
        $isValid = true;

        /** @var Mage_Sales_Model_Quote_Payment | Mage_Sales_Model_Order_Payment $infoInstance */
        $infoInstance = $this->getInfoInstance();

        /**
         * Don't need to validate quote object, only the order.
         */
        if ($infoInstance instanceof Mage_Sales_Model_Quote_Payment) {
            return parent::validate();
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $infoInstance->getOrder();

        if (!$order || !$order->getId()) {
            $isValid = false;
        }

        /** @var array $parameters */
        $parameters = $this->_processor()->getOrderParams($order);

        $validation = $this->_validateOrderParameters($parameters);
        if ($validation !== true) {
            Mage::throwException($validation);
        }

        if ($isValid == false) {
            Mage::throwException($this->_helper()->__('Something went wrong in the payment process.'));
        }

        $this->_validated = true;

        return parent::validate();
    }


    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     *
     * @return $this
     */
    protected function _cancel(Varien_Object $payment)
    {
        // $payment->getOrder()->cancel();
        // $payment->cancel();

        return $this;
    }

}
