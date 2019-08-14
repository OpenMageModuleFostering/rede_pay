<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Payments
 *
 * @method string getPaymentId()
 * @method string getTransactionId()
 * @method int    getOrderId()
 * @method string getOrderIncrementId()
 * @method array  getAdditionalInformation()
 * @method array  getAdditionalInformationSerialized()
 * @method int    getPaymentRetries()
 *
 * @method $this setPaymentId(string $paymentId)
 * @method $this setTransactionId(string $transactionId)
 * @method $this setOrderId(int $orderId)
 * @method $this setOrderIncrementId(int $orderIncrementId)
 * @method $this setAdditionalInformation(array $additionalInformation)
 * @method $this setAdditionalInformationSerialized(array $additionalInformation)
 * @method $this setPaymentRetries(int $times)
 */
class Rede_Pay_Model_Payments extends Rede_Pay_Model_Abstract
{

    /** @var string */
    protected $_eventObject = 'payments';

    /** @var string */
    protected $_eventPrefix = 'rede_pay_payments';


    protected function _construct()
    {
        $this->_init('rede_pay/payments');
        parent::_construct();
    }


    /**
     * @param int $orderId
     *
     * @return $this
     */
    public function loadByOrderId($orderId)
    {
        return $this->load($orderId, 'order_id');
    }


    /**
     * @param int $incrementId
     *
     * @return $this
     */
    public function loadByOrderIncrementId($incrementId)
    {
        return $this->load($incrementId, 'order_increment_id');
    }


    /**
     * @param string $transactionId
     *
     * @return $this
     */
    public function loadByTransactionId($transactionId)
    {
        return $this->load($transactionId, 'transaction_id');
    }


    /**
     * @param string $paymentId
     *
     * @return $this
     */
    public function loadByPaymentId($paymentId)
    {
        return $this->load($paymentId, 'payment_id');
    }


    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->getData('additional_information/status');
    }


    /**
     * @return string
     */
    public function getCardBrand()
    {
        return $this->getData('additional_information/cardBrand');
    }


    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->getData('additional_information/paymentMethod');
    }


    /**
     * @return string
     */
    public function getNsu()
    {
        return $this->getData('additional_information/nsu');
    }


    /**
     * @return string
     */
    public function getTid()
    {
        return $this->getData('additional_information/tId');
    }


    /**
     * @return int
     */
    public function getInstallments()
    {
        return $this->getData('additional_information/installments');
    }


    /**
     * @return $this
     */
    public function incrementRetry()
    {
        $times = $this->getPaymentRetries();
        $times++;

        $this->setPaymentRetries($times);

        return $this;
    }


    /**
     * @return $this
     */
    protected function _beforeSave()
    {
        $additionalInformation = $this->getAdditionalInformation();

        if (!empty($additionalInformation) && is_array($additionalInformation)) {
            $this->setAdditionalInformationSerialized(serialize($additionalInformation));
        }

        return parent::_beforeSave();
    }


    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function _afterLoad()
    {
        $additionalInformation = $this->getAdditionalInformationSerialized();

        if (!empty($additionalInformation) && is_string($additionalInformation)) {
            $this->setAdditionalInformation(unserialize($additionalInformation));
        }

        return parent::_afterLoad();
    }

}
