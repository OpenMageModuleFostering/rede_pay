<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Processor
 */
class Rede_Pay_Model_Processor
{

    use Rede_Pay_Trait_Data;

    /** @var Mage_Sales_Model_Order */
    protected $_order = null;

    /** @var array */
    protected $_cache = [];


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
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function getOrderParams(Mage_Sales_Model_Order $order = null)
    {
        if ($order) {
            $this->setOrder($order);
        }

        return $this->_getParams('order');
    }


    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function getOrderJson(Mage_Sales_Model_Order $order = null)
    {
        if ($order) {
            $this->setOrder($order);
        }

        return $this->_getJson('order');
    }


    /**
     * @param Mage_Sales_Model_Order|null $order
     *
     * @return string
     */
    public function getRefundParams(Mage_Sales_Model_Order $order = null)
    {
        if ($order) {
            $this->setOrder($order);
        }

        return $this->_getParams('refund');
    }


    /**
     * @param Mage_Sales_Model_Order|null $order
     *
     * @return string
     */
    public function getRefundJson(Mage_Sales_Model_Order $order = null)
    {
        if ($order) {
            $this->setOrder($order);
        }

        return $this->_getJson('refund');
    }


    /**
     * @param Mage_Sales_Model_Order|null $order
     *
     * @return string
     */
    public function getConsultParams(Mage_Sales_Model_Order $order = null)
    {
        if ($order) {
            $this->setOrder($order);
        }

        return $this->_getParams('consult');
    }


    /**
     * @param Mage_Sales_Model_Order|null $order
     *
     * @return string
     */
    public function getConsultJson(Mage_Sales_Model_Order $order = null)
    {
        if ($order) {
            $this->setOrder($order);
        }

        return $this->_getJson('consult');
    }


    /**
     * @param Mage_Sales_Model_Order|null $order
     *
     * @return string
     */
    public function getShippingTrackingParams($trackingNumber, Mage_Sales_Model_Order $order = null)
    {
        if ($order) {
            $this->setOrder($order);
        }

        return $this->_getParams('shipping_tracking', ['tracking_number' => $trackingNumber]);
    }


    /**
     * @param Mage_Sales_Model_Order|null $order
     *
     * @return string
     */
    public function getShippingTrackingJson($trackingNumber, Mage_Sales_Model_Order $order = null)
    {
        if ($order) {
            $this->setOrder($order);
        }

        return $this->_getJson('shipping_tracking', ['tracking_number' => $trackingNumber]);
    }


    /**
     * @param Mage_Sales_Model_Order|null $order
     *
     * @return string
     */
    public function getShippingTrackingDeleteJson(Mage_Sales_Model_Order $order = null)
    {
        if ($order) {
            $this->setOrder($order);
        }

        return $this->_getJson('shipping_tracking_delete');
    }


    /**
     * @param string $type
     *
     * @return array|bool|string
     */
    protected function _getParams($type, $data = [])
    {
        return $this->_getData($type, 'parameters', $data);
    }


    /**
     * @param string $type
     * @param array  $data
     *
     * @return array|bool|string
     */
    protected function _getJson($type, $data = [])
    {
        return $this->_getData($type, 'json', $data);
    }


    /**
     * @param string $type
     *
     * @param string $method
     *
     * @return bool|array|string
     */
    protected function _getData($type, $method, $data = [])
    {
        $cacheKey = implode('_', [$type, $method, md5(serialize($data))]);

        if (!empty($this->_cache[$cacheKey])) {
            return $this->_cache[$cacheKey];
        }

        /** @var Rede_Pay_Model_Params_Abstract $parameters */
        $parameters = Mage::getSingleton('rede_pay/params_' . strtolower($type));

        if (!$parameters || (!$parameters instanceof Rede_Pay_Model_Params_Abstract)) {
            return false;
        }

        $methodName = 'get'.ucfirst($method);

        if (!method_exists($parameters, $methodName)) {
            return false;
        }

        if (!empty($data)) {
            $parameters->setData($data);
        }

        $this->_cache[$cacheKey] = $parameters->{$methodName}($this->getOrder());
        return $this->_cache[$cacheKey];
    }

}
