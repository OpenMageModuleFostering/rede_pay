<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Session
 */
class Rede_Pay_Model_Session extends Mage_Core_Model_Session_Abstract
{

    use Rede_Pay_Trait_Data;

    /**
     * Class constructor. Initialize Rede Pay session namespace
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('rede_pay');
    }


    /**
     * @param Rede_Pay_Model_Payments $payments
     *
     * @return $this
     */
    public function setLastTransaction(Rede_Pay_Model_Payments $payments)
    {
        $this->setData('last_transaction_info', $payments->getData());
        return $this;
    }


    /**
     * @return mixed
     */
    public function getLastTransaction()
    {
        return $this->getData('last_transaction_info');
    }


    /**
     * @return null|int
     */
    public function getLastOrderId()
    {
        return $this->getData('last_transaction_info/order_id');
    }


    /**
     * @return null|string
     */
    public function getLastOrderIncrementId()
    {
        return $this->getData('last_transaction_info/order_increment_id');
    }


    /**
     * @return null|string
     */
    public function getLastPaymentId()
    {
        return $this->getData('last_transaction_info/payment_id');
    }


    /**
     * @return $this
     */
    public function clearLastTransaction()
    {
        $this->unsetData('last_transaction_info');
        return $this;
    }

}
