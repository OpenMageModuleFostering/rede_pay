<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Logger
 */
class Rede_Pay_Model_Logger
{

    use Rede_Pay_Trait_Data;

    const LOG_FILENAME_DEFAULT      = 'Rede_Pay';
    const LOG_FILENAME_NOTIFICATION = 'Rede_Pay-Notification';
    const LOG_FILE_EXTENSION        = 'log';

    protected $_fileName       = null;
    protected $_fileExtension  = null;


    /**
     * @param Zend_Http_Client $client
     *
     * @return $this
     */
    public function logHeaders(Zend_Http_Client $client)
    {
        if (!$client) {
            return $this;
        }

        $parameters = array(
            'access-token',
            'version',
            'Content-Type',
            'Accept'
        );

        foreach ($parameters as $parameter) {
            $this->log($this->_helper()->__('Param: %s = %s', $parameter, $client->getHeader($parameter)));
        }

        return $this;
    }


    public function logMethod($method = null)
    {
        if (!$method) {
            return $this;
        }

        $methods = array(
            Zend_Http_Client::GET,
            Zend_Http_Client::POST,
            Zend_Http_Client::PUT,
            Zend_Http_Client::HEAD,
            Zend_Http_Client::DELETE,
        );

        if (!in_array($method, $methods)) {
            return $this;
        }

        $this->log($this->_helper()->__('Method: %s', $method));

        return $this;
    }


    /**
     * @param int $orderId
     *
     * @return $this
     */
    public function startTransactionLog($orderId = null)
    {
        if (empty($orderId)) {
            return $this;
        }

        $this->transactionLog($this->_helper()->__('Starting payment for order #%s', $orderId));
        $this->log($this->_helper()->__('Magento URL: %s.', $this->_getCurrentUrl()));

        return $this;
    }


    /**
     * @param int $orderId
     *
     * @return $this
     */
    public function startConsultLog($orderId = null)
    {
        if (empty($orderId)) {
            return $this;
        }

        $this->transactionLog($this->_helper()->__('Starting consult for order #%s', $orderId));
        $this->log($this->_helper()->__('Magento URL: %s.', $this->_getCurrentUrl()));

        return $this;
    }


    /**
     * @param int $orderId
     *
     * @return $this
     */
    public function startShippingTrackingLog($orderId = null)
    {
        if (empty($orderId)) {
            return $this;
        }

        $this->transactionLog($this->_helper()->__('Starting shipping tracking for order #%s', $orderId));
        $this->log($this->_helper()->__('Magento URL: %s.', $this->_getCurrentUrl()));

        return $this;
    }


    /**
     * @param int $orderId
     *
     * @return $this
     */
    public function startRefundLog($orderId = null)
    {
        if (empty($orderId)) {
            return $this;
        }

        $this->transactionLog($this->_helper()->__('Starting refund for order #%s', $orderId));
        $this->log($this->_helper()->__('Magento URL: %s.', $this->_getCurrentUrl()));

        return $this;
    }


    /**
     * @param int $orderId
     *
     * @return $this
     */
    public function finishTransactionLog($orderId = null)
    {
        if (empty($orderId)) {
            return $this;
        }

        $this->transactionLog($this->_helper()->__('Finishing payment for order #%s', $orderId));
        $this->log(PHP_EOL);

        return $this;
    }


    /**
     * @param int $orderId
     *
     * @return $this
     */
    public function finishConsultLog($orderId = null)
    {
        if (empty($orderId)) {
            return $this;
        }

        $this->transactionLog($this->_helper()->__('Finishing consult for order #%s', $orderId));
        $this->log(PHP_EOL);

        return $this;
    }


    /**
     * @param int $orderId
     *
     * @return $this
     */
    public function finishShippingTrackingLog($orderId = null)
    {
        if (empty($orderId)) {
            return $this;
        }

        $this->transactionLog($this->_helper()->__('Finishing shipping tracking for order #%s', $orderId));
        $this->log(PHP_EOL);

        return $this;
    }


    /**
     * @param int $orderId
     *
     * @return $this
     */
    public function finishRefundLog($orderId = null)
    {
        if (empty($orderId)) {
            return $this;
        }

        $this->transactionLog($this->_helper()->__('Finishing refund for order #%s', $orderId));
        $this->log(PHP_EOL);

        return $this;
    }
    

    /**
     * @param string $message
     *
     * @return $this
     */
    public function transactionLog($message = null)
    {
        $message = str_pad($message, $this->getSpacerLength(), '-', STR_PAD_RIGHT);

        $this->log($message);

        return $this;
    }


    /**
     * @param string $message
     *
     * @return $this
     */
    public function notificationLog($params = array())
    {
        $this->setFilename(self::LOG_FILENAME_NOTIFICATION);
        $this->log($params);
        $this->setFilename(self::LOG_FILENAME_DEFAULT);

        return $this;
    }


    /**
     * @param string|array $message
     * @param bool         $translate
     * @param bool         $force
     *
     * @return $this
     */
    public function log($message, $translate = true, $force = false)
    {
        if (($translate == true) && is_string($message)) {
            $message = $this->_helper()->__($message);
        }

        Mage::log($message, null, $this->getFilename(), $force);

        return $this;
    }


    /**
     * @return string
     */
    public function getFilename()
    {
        $fileName  = empty($this->_fileName)      ? self::LOG_FILENAME_DEFAULT : $this->_fileName;
        $extension = empty($this->_fileExtension) ? self::LOG_FILE_EXTENSION   : $this->_fileExtension;

        return $fileName . '.' . $extension;
    }


    /**
     * @param string $filename
     *
     * @return $this
     */
    public function setFilename($filename = self::LOG_FILENAME_DEFAULT)
    {
        $this->_fileName = $filename;
        return $this;
    }


    /**
     * @param string $extension
     *
     * @return $this
     */
    public function setFileExtension($extension = self::LOG_FILE_EXTENSION)
    {
        $extension = str_replace('.', null, $extension);

        $this->_fileExtension = $extension;
        return $this;
    }


    /**
     * @return int
     */
    public function getSpacerLength()
    {
        return 120;
    }


    /**
     * @return string
     */
    protected function _getCurrentUrl()
    {
        return Mage::app()->getRequest()->getRequestUri();
    }

}
