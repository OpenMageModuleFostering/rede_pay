<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Api
 */
class Rede_Pay_Model_Api
{

    use Rede_Pay_Trait_Data;

    /** These two statuses were discontinued. */
    const STATUS_APPROVED    = 'APPROVED';
    const STATUS_DENIED      = 'DENIED';

    /** These are the valid ones. */
    const STATUS_OPEN        = 'OPEN';
    const STATUS_CANCELLED   = 'CANCELLED';
    const STATUS_COMPLETED   = 'COMPLETED';
    const STATUS_IN_DISPUTE  = 'IN_DISPUTE';
    const STATUS_REVERSED    = 'REVERSED';
    const STATUS_CHARGEBACK  = 'CHARGEBACK';
    const STATUS_IN_ANALYSIS = 'IN_ANALYSIS';


    /** @var Zend_Http_Client */
    protected $_client  = null;

    /** @var Zend_Http_Response */
    protected $_result  = null;

    /** @var string */
    protected $_rawBody = null;

    /** @var array */
    protected $_body    = null;


    /**
     * @return $this
     */
    public function clean()
    {
        $this->_client  = null;
        $this->_result  = null;
        $this->_rawBody = null;
        $this->_body    = null;

        return $this;
    }


    /**
     * Return the allowed statuses for orders in API.
     *
     * @return array
     */
    public static function getAllowedStatuses()
    {
        return array(
            self::STATUS_OPEN,
            self::STATUS_APPROVED,
            self::STATUS_REVERSED,
            self::STATUS_IN_DISPUTE,
            self::STATUS_CHARGEBACK,
            self::STATUS_IN_ANALYSIS,
            self::STATUS_DENIED,
            self::STATUS_CANCELLED,
            self::STATUS_COMPLETED
        );
    }


    /**
     * Return the approved statuses for orders in API.
     *
     * @return array
     */
    public static function getApprovedStatuses()
    {
        return array(
            self::STATUS_APPROVED,
            self::STATUS_COMPLETED
        );
    }


    /**
     * Return the approved statuses for orders in API.
     *
     * @return array
     */
    public static function getCancelledStatuses()
    {
        return array(
            self::STATUS_DENIED,
            self::STATUS_CANCELLED
        );
    }


    /**
     * Return the approved statuses for orders in API.
     *
     * @return array
     */
    public static function getOpenStatuses()
    {
        return array(
            self::STATUS_OPEN,
        );
    }


    /**
     * Return the in analysis statuses for orders in API.
     *
     * @return array
     */
    public static function getInAnalysisStatuses()
    {
        return array(
            self::STATUS_IN_ANALYSIS
        );
    }


    /**
     * Check if given status is approved.
     *
     * @param string $status
     *
     * @return bool
     */
    public function isStatusApproved($status)
    {
        return (bool) in_array($status, $this->getApprovedStatuses());
    }


    /**
     * Check if given status is approved.
     *
     * @param string $status
     *
     * @return bool
     */
    public function isStatusInAnalysis($status)
    {
        return (bool) in_array($status, $this->getInAnalysisStatuses());
    }


    /**
     * Check if given status is cancelled.
     *
     * @param string $status
     *
     * @return bool
     */
    public function isStatusCancelled($status)
    {
        return (bool) in_array($status, $this->getCancelledStatuses());
    }


    /**
     * Check if given status is cancelled.
     *
     * @param string $status
     *
     * @return bool
     */
    public function isStatusOpen($status)
    {
        return (bool) in_array($status, $this->getOpenStatuses());
    }


    /**
     * Check if given status is a valid one.
     *
     * @param string $status
     *
     * @return bool
     */
    public function isStatusAllowed($status)
    {
        return (bool) in_array($status, $this->getAllowedStatuses());
    }


    /**
     * @return Zend_Http_Client
     *
     * @throws Zend_Http_Client_Exception
     */
    public function getClient()
    {
        if (!$this->_client) {
            $secretKey = $this->_helper()->getConfigSecretApiKey();

            $this->_client = new Zend_Http_Client();

            $this->_client->setHeaders('access-token', $secretKey);
            $this->_client->setHeaders('version'     , 'v1');
            $this->_client->setHeaders('Content-Type', 'application/json');
            $this->_client->setHeaders('Accept'      , 'application/json');

            $this->_client->setMethod(Zend_Http_Client::POST);
        }

        return $this->_client;
    }


    /**
     * @return $this
     *
     * @throws Zend_Http_Client_Exception
     */
    public function request($method = 'order')
    {
        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            Mage::throwException($this->_helper()->__("Method '%s' does not exist.", $method));
        }

        return $this;
    }


    /**
     * Do the order request in API.
     *
     * @var Mage_Sales_Model_Order $order
     *
     * @return $this
     *
     * @throws Zend_Http_Client_Exception
     */
    public function order(Mage_Sales_Model_Order $order = null)
    {
        $this->clean();

        if ($order && $order->getId()) {
            $this->_processor()->setOrder($order);
        }

        $json = $this->_processor()->getOrderJson();

        $this->getClient()->setRawData($json, 'application/json');
        $this->getClient()->setUri($this->_helper()->getConfigApiOrderUrl());

        $this->_logger()->startTransactionLog($this->getOrder()->getRealOrderId());
        $this->_logger()->logHeaders($this->_client);
        $this->_logger()->logMethod(Zend_Http_Client::POST);
        $this->_logger()->log($this->_helper()->__('Transaction URL: %s.', $this->getClient()->getUri()));
        $this->_logger()->log($this->_helper()->__('Order Request: %s.', $json), false);

        $this->_result  = $this->getClient()->request();
        $this->_rawBody = $this->_result->getBody();
        $this->_body    = $this->_helper()->jsonDecode($this->_rawBody);

        $this->_logger()->log($this->_helper()->__('Order Response: %s.', $this->_rawBody), false);

        $this->_logger()->finishTransactionLog($this->getOrder()->getRealOrderId());

        return $this;
    }


    /**
     * Do refund request in the API.
     *
     * @var string                 $transactionId
     * @var Mage_Sales_Model_Order $order
     *
     * @return $this
     *
     * @throws Zend_Http_Client_Exception
     */
    public function refund($transactionId = null, Mage_Sales_Model_Order $order = null)
    {
        $this->clean();

        if ($order && $order->getId()) {
            $this->_processor()->setOrder($order);
        }

        $json = $this->_processor()->getRefundJson();

        $this->getClient()->setRawData($json, 'application/json');
        $this->getClient()->setUri($this->_helper()->getConfigApiRefundUrl($transactionId));
        $this->getClient()->setMethod(Zend_Http_Client::PUT);

        $this->_logger()->startRefundLog($this->getOrder()->getRealOrderId());
        $this->_logger()->logHeaders($this->_client);
        $this->_logger()->logMethod(Zend_Http_Client::PUT);
        $this->_logger()->log($this->_helper()->__('Refund URL: %s.', $this->getClient()->getUri()));
        $this->_logger()->log($this->_helper()->__('Refund Request: %s.', $json), false);

        $this->_result  = $this->getClient()->request();
        $this->_rawBody = $this->_result->getBody();
        $this->_body    = $this->_helper()->jsonDecode($this->_rawBody);

        $this->_logger()->log($this->_helper()->__('Refund Response: %s.', $this->_rawBody), false);

        $this->_logger()->finishRefundLog($this->getOrder()->getRealOrderId());

        return $this;
    }


    /**
     * Do consult request in the API.
     *
     * @var string                 $transactionId
     * @var Mage_Sales_Model_Order $order
     *
     * @return $this
     *
     * @throws Zend_Http_Client_Exception
     */
    public function consult($transactionId = null, Mage_Sales_Model_Order $order = null)
    {
        $this->clean();

        if ($order && $order->getId()) {
            $this->_processor()->setOrder($order);
        }

        $json = $this->_processor()->getConsultJson();

        $this->getClient()->setRawData($json, 'application/json');
        $this->getClient()->setUri($this->_helper()->getConfigApiConsultUrl($transactionId));
        $this->getClient()->setMethod(Zend_Http_Client::GET);

        $this->_logger()->startConsultLog($this->getOrder()->getRealOrderId());
        $this->_logger()->logHeaders($this->_client);
        $this->_logger()->logMethod(Zend_Http_Client::GET);
        $this->_logger()->log($this->_helper()->__('Consult URL: %s.', $this->getClient()->getUri()));
        $this->_logger()->log($this->_helper()->__('Consult Request: %s.', $json), false);

        try {
            $this->_result  = $this->getClient()->request();
            $this->_rawBody = $this->_result->getBody();
            $this->_body    = $this->_helper()->jsonDecode($this->_rawBody);

            $this->_logger()->log($this->_helper()->__('Consult Response: %s.', $this->_rawBody), false);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_logger()->log('Exception: ' . $e->getMessage(), false);
        }

        $this->_logger()->finishConsultLog($this->getOrder()->getRealOrderId());

        return $this;
    }


    /**
     * Do consult order request in the API.
     *
     * @var string                 $paymentId
     * @var Mage_Sales_Model_Order $order
     *
     * @return $this
     *
     * @throws Zend_Http_Client_Exception
     */
    public function consultOrder($paymentId = null, Mage_Sales_Model_Order $order = null)
    {
        $this->clean();

        if ($order && $order->getId()) {
            $this->_processor()->setOrder($order);
        }

        $json = $this->_processor()->getConsultJson();

        $this->getClient()->setRawData($json, 'application/json');
        $this->getClient()->setUri($this->_helper()->getConfigApiConsultOrderUrl($paymentId));
        $this->getClient()->setMethod(Zend_Http_Client::GET);

        $this->_logger()->startConsultLog($this->getOrder()->getRealOrderId());
        $this->_logger()->logHeaders($this->_client);
        $this->_logger()->logMethod(Zend_Http_Client::GET);
        $this->_logger()->log($this->_helper()->__('Consult URL: %s.', $this->getClient()->getUri()));
        $this->_logger()->log($this->_helper()->__('Consult Order Request: %s.', $json), false);

        try {
            $this->_result  = $this->getClient()->request();
            $this->_rawBody = $this->_result->getBody();
            $this->_body    = $this->_helper()->jsonDecode($this->_rawBody);

            $this->_logger()->log($this->_helper()->__('Consult Order Response: %s.', $this->_rawBody), false);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_logger()->log('Exception: ' . $e->getMessage(), false);
        }

        $this->_logger()->finishConsultLog($this->getOrder()->getRealOrderId());

        return $this;
    }


    /**
     * @param string                 $trackingNumber
     * @param string                 $transactionId
     * @param Mage_Sales_Model_Order $order
     *
     * @return $this
     */
    public function addShippingTracking($trackingNumber, $transactionId, Mage_Sales_Model_Order $order = null)
    {
        $this->clean();

        if ($order && $order->getId()) {
            $this->_processor()->setOrder($order);
        }

        $json = $this->_processor()->getShippingTrackingJson($trackingNumber);

        $this->getClient()->setRawData($json, 'application/json');
        $this->getClient()->setUri($this->_helper()->getConfigApiShippingTrackingUrl($transactionId));
        $this->getClient()->setMethod(Zend_Http_Client::POST);

        $this->_logger()->startShippingTrackingLog($this->getOrder()->getRealOrderId());
        $this->_logger()->logHeaders($this->_client);
        $this->_logger()->logMethod(Zend_Http_Client::POST);
        $this->_logger()->log($this->_helper()->__('Add Shipping Tracking URL: %s.', $this->getClient()->getUri()));
        $this->_logger()->log($this->_helper()->__('Add Shipping Tracking Request: %s.', $json), false);

        try {

            $this->_result  = $this->getClient()->request();
            $this->_rawBody = $this->_result->getBody();
            $this->_body    = $this->_helper()->jsonDecode($this->_rawBody);

            $this->_logger()->log($this->_helper()->__('Add Shipping Tracking Response: %s.', $this->_rawBody), false);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_logger()->log('Exception: ' . $e->getMessage(), false);
        }

        $this->_logger()->finishShippingTrackingLog($this->getOrder()->getRealOrderId());

        return $this;
    }


    /**
     * @param string                 $transactionId
     * @param Mage_Sales_Model_Order $order
     *
     * @return $this
     */
    public function deleteShippingTracking($paymentId, Mage_Sales_Model_Order $order = null)
    {
        $this->clean();

        if ($order && $order->getId()) {
            $this->_processor()->setOrder($order);
        }

        $json = $this->_processor()->getShippingTrackingDeleteJson();

        $this->getClient()->setRawData($json, 'application/json');
        $this->getClient()->setUri($this->_helper()->getConfigApiShippingTrackingUrl($paymentId));
        $this->getClient()->setMethod(Zend_Http_Client::DELETE);

        $this->_logger()->startShippingTrackingLog($this->getOrder()->getRealOrderId());
        $this->_logger()->logHeaders($this->_client);
        $this->_logger()->logMethod(Zend_Http_Client::DELETE);
        $this->_logger()->log($this->_helper()->__('Delete Shipping Tracking URL: %s.', $this->getClient()->getUri()));
        $this->_logger()->log($this->_helper()->__('Delete Shipping Tracking Request: %s.', $json), false);

        try {

            $this->_result  = $this->getClient()->request();
            $this->_rawBody = $this->_result->getBody();
            $this->_body    = $this->_helper()->jsonDecode($this->_rawBody);

            $this->_logger()->log($this->_helper()->__('Delete Shipping Tracking Response: %s.', $this->_rawBody), false);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_logger()->log('Exception: ' . $e->getMessage(), false);
        }

        $this->_logger()->finishShippingTrackingLog($this->getOrder()->getRealOrderId());

        return $this;
    }


    /**
     * @return Zend_Http_Response
     */
    public function getResult()
    {
        return $this->_result;
    }


    /**
     * @return string
     */
    public function getRawBody()
    {
        return $this->_rawBody;
    }


    /**
     * @return array
     */
    public function getBody()
    {
        return $this->_body;
    }


    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->_processor()->getOrder();
    }


    /**
     * @return bool
     */
    public function getIsOrderFound()
    {
        switch ($this->getResult()->getStatus()) {
            case 200:
                return true;
                break;

            case 404:
            default:
                return false;
                break;
        }
    }

}
