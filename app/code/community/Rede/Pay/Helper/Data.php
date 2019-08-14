<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Helper_Data
 */
class Rede_Pay_Helper_Data extends Mage_Core_Helper_Data
{

    const DEFAULT_LIGHTBOX_DELAY_TIME = 5;


    /**
     * @param string $field
     *
     * @return string
     */
    public function getPaymentConfig($field)
    {
        return Mage::getStoreConfig(implode('/', array('payment', 'rede_pay', $field)));
    }


    /**
     * @return int
     */
    public function getLightboxDelaySeconds()
    {
        $seconds = $this->getPaymentConfig('lightbox_delay_time');

        if (is_null($seconds)) {
            $seconds = self::DEFAULT_LIGHTBOX_DELAY_TIME;
        }

        return (int) $seconds;
    }


    /**
     * @return int
     */
    public function getLightboxDelayMiliseconds()
    {
        return (int) ($this->getLightboxDelaySeconds() * 1000);
    }


    /**
     * @return int
     */
    public function getConfigInstallmentsAmount()
    {
        return (int) $this->getPaymentConfig('installments_amount');
    }


    /**
     * @return int
     */
    public function getConfigInstallmentsMinOrderValue()
    {
        return (int) $this->getPaymentConfig('installments_min_order_value');
    }


    /**
     * @return int
     */
    public function getConfigInstallmentsMinParcelValue()
    {
        return (int) $this->getPaymentConfig('installments_min_parcel_value');
    }


    /**
     * @return string
     */
    public function getConfigApiOrderUrl()
    {
        return $this->getPaymentConfig('api_order_url');
    }


    /**
     * @param null $transactionId
     *
     * @return string
     */
    public function getConfigApiConsultUrl($transactionId = null)
    {
        $url = $this->getPaymentConfig('api_consult_url');

        if (substr($url, -1) !== '/') {
            $url .= '/';
        }

        if (!is_null($transactionId)) {
            $url .= $transactionId;
        }

        return $url;
    }


    /**
     * @param string $paymentId
     *
     * @return string
     */
    public function getConfigApiConsultOrderUrl($paymentId = null)
    {
        $url = $this->getPaymentConfig('api_consult_order_url');

        if (substr($url, -1) !== '/') {
            $url .= '/';
        }

        if (!is_null($paymentId)) {
            $url .= $paymentId;
        }

        return $url;
    }


    /**
     * @return string
     */
    public function getConfigApiRefundUrl($paymentId = null)
    {
        $url = $this->getPaymentConfig('api_refund_url');

        if (substr($url, -1) !== '/') {
            $url .= '/';
        }

        if (!is_null($paymentId)) {
            $url .= $paymentId;
        }

        return $url;
    }


    /**
     * @return string
     */
    public function getConfigApiShippingTrackingUrl($transactionId)
    {
        $url = $this->getPaymentConfig('api_shipping_tracking_url');

        if (substr($url, -1) !== '/') {
            $url .= '/';
        }

        $url .= $transactionId . '/trackings';

        return $url;
    }


    /**
     * @return string
     */
    public function getConfigRedePayScriptUrl()
    {
        return $this->getPaymentConfig('redepay_script_url');
    }


    /**
     * @return string
     */
    public function getConfigSecretApiKey()
    {
        $key = 'secret_api_key';

        if ($this->isTestMode()) {
            $key .= '_test';
        }

        return $this->getPaymentConfig($key);
    }


    /**
     * @return string
     */
    public function getConfigNotificationToken()
    {
        $key = 'notification_token';

        if ($this->isTestMode()) {
            $key .= '_test';
        }

        return $this->getPaymentConfig($key);
    }


    /**
     * @return string
     */
    public function getConfigPublishableApiKey()
    {
        $key = 'publishable_api_key';

        if ($this->isTestMode()) {
            $key .= '_test';
        }

        return $this->getPaymentConfig($key);
    }


    /**
     * @return bool
     */
    public function isTestMode()
    {
        return (bool) $this->getPaymentConfig('test');
    }


    /**
     * @return string
     */
    public function getConfigTextMessage()
    {
        return (string) $this->getPaymentConfig('text_message');
    }


    /**
     * @param int $orderId
     *
     * @return string
     */
    public function getNotificationUrl($orderId = null)
    {
        return $this->_prepareUrl('redepay/notification', $orderId);
    }


    /**
     * @param int $orderId
     *
     * @return string
     */
    public function getRedirectUrl($orderId = null)
    {
        return $this->_prepareUrl('redepay/notification/redirect', $orderId);
    }


    /**
     * @param int $orderId
     *
     * @return string
     */
    public function getCancelUrl($orderId = null)
    {
        return $this->_prepareUrl('redepay/notification/cancel', $orderId);
    }
    

    /**
     * @param int $width
     *
     * @return Rede_Pay_Block_Logo
     */
    public function getLogoBlock($width = null)
    {
        return Mage::app()->getLayout()->createBlock('rede_pay/logo')->setWidth($width);
    }


    /**
     * @param int $width
     *
     * @return string
     */
    public function getLogoHtml($width = null)
    {
        return $this->getLogoBlock($width)->toHtml();
    }


    /**
     * @param Mage_Sales_Model_Order $order
     * @param array                  $result
     *
     * @return string
     */
    public function processOrderConsultStatus(Mage_Sales_Model_Order $order, $status = null, $result = array(),
                                              $processType = null)
    {
        return $this->getOrderProcessor()->processOrderConsultStatus($order, $status, $result, $processType);
    }
    

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string                 $status
     * @param string                 $process
     *
     * @return $this
     *
     * @throws Exception
     * @throws bool
     */
    public function processOrderStatus(Mage_Sales_Model_Order $order, $status, $processType = null)
    {
        return $this->getOrderProcessor()->processOrderStatus($order, $status, $processType);
    }


    /**
     * @return Rede_Pay_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('rede_pay/session');
    }


    /**
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }


    /**
     * @return Mage_Adminhtml_Model_Session
     */
    public function getAdminSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }


    /**
     * @return Rede_Pay_Model_Logger
     */
    public function getLogger()
    {
        return Mage::getSingleton('rede_pay/logger');
    }


    /**
     * @return Rede_Pay_Model_Processor_Order
     */
    public function getOrderProcessor()
    {
        return Mage::getSingleton('rede_pay/processor_order');
    }


    /**
     * @return Rede_Pay_Model_Processor
     */
    public function getProcessor()
    {
        return Mage::getSingleton('rede_pay/processor');
    }


    /**
     * @param string $route
     * @param int    $orderId
     *
     * @return string
     */
    protected function _prepareUrl($route, $orderId = null)
    {
        $parameters = array();

        if ($orderId) {
            $parameters['order_id'] = $orderId;
        }

        $base = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $full = $base . $route;

        foreach ($parameters as $key => $value) {
            $full .= '/' . $key . '/' . $value;
        }

        return $full;
    }

}
