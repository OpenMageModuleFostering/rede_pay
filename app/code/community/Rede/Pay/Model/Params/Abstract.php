<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Params_Abstract
 */
abstract class Rede_Pay_Model_Params_Abstract
{

    use Rede_Pay_Trait_Data;

    const MAX_INSTALLMENTS_AMOUNT = 12;

    /** @var Mage_Sales_Model_Order */
    protected $_order             = null;

    /** @var array */
    protected $_data              = array();

    /** @var array */
    protected $_parameters        = array();

    /** @var string */
    protected $_parametersEncoded = null;

    /** @var bool */
    protected $_isPrepared        = false;

    /** @var bool */
    protected $_requireData       = true;


    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return $this
     */
    public function setOrder(Mage_Sales_Model_Order $order)
    {
        if (!$order || !$order->getId()) {
            return $this;
        }

        $this->_order = $order;

        return $this;
    }


    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data = [])
    {
        $this->_data = $data;

        return $this;
    }


    /**
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function addData($key, $value)
    {
        $this->_data[$key] = $value;

        return $this;
    }


    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->_order;
    }


    /**
     * @return $this
     */
    public function resetOrder()
    {
        $this->_order = null;
        return $this;
    }


    /**
     * @return string
     *
     * @throws Mage_Core_Exception
     */
    public function getJson(Mage_Sales_Model_Order $order = null)
    {
        if ($order && !$this->_isPrepared) {
            $this->prepareData($order);
        }

        if ($this->_requireData === false) {
            return $this->_parametersEncoded;
        }

        if (empty($this->_parameters) && empty($this->_parametersEncoded)) {
            Mage::throwException($this->_helper()->__('Incorrect parameters in processor.'));
        }

        if (empty($this->_parametersEncoded)) {
            $this->_parametersEncoded = $this->_helper()->jsonEncode($this->_parameters);
        }

        return $this->_parametersEncoded;
    }


    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     *
     * @throws Mage_Core_Exception
     */
    public function getParameters(Mage_Sales_Model_Order $order = null)
    {
        if ($order && !$this->_isPrepared) {
            $this->prepareData($order);
        }

        if (empty($this->_parameters)) {
            Mage::throwException($this->_helper()->__('Incorrect parameters in processor.'));
        }

        return $this->_parameters;
    }


    /**
     * @return Mage_Sales_Model_Order_Address
     */
    public function getAddress()
    {
        if ($this->getOrder()->getIsVirtual()) {
            return $this->getOrder()->getBillingAddress();
        }

        return $this->getOrder()->getShippingAddress();
    }


    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return $this
     *
     * @throws Mage_Core_Exception
     */
    public function prepareData(Mage_Sales_Model_Order $order = null)
    {
        if ($order) {
            $this->setOrder($order);
        }

        if (!$this->_validateOrder()) {
            Mage::throwException($this->_helper()->__('Order object is not set.'));
        }

        $this->_prepareData();

        return $this;
    }


    /**
     * @return $this
     */
    abstract protected function _prepareData($force = false);


    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return bool
     */
    protected function _validateOrder()
    {
        return (bool) ($this->getOrder() && $this->getOrder()->getId());
    }


    /**
     * @return Rede_Pay_Helper_Attributes
     */
    protected function _attributesHelper()
    {
        return Mage::helper('rede_pay/attributes');
    }

}
