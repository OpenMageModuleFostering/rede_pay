<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Trait_Data
 */
trait Rede_Pay_Trait_Data
{

    private $_moduleName = null;

    /**
     * @return string
     */
    public function __()
    {
        $args = func_get_args();
        $expr = new Mage_Core_Model_Translate_Expr(array_shift($args), 'Rede_Pay');
        array_unshift($args, $expr);
        return Mage::app()->getTranslator()->translate($args);
    }
    

    /**
     * @param bool $singleton
     *
     * @return Rede_Pay_Model_Api
     */
    public function getApi($singleton = true)
    {
        if (false === $singleton) {
            return Mage::getModel('rede_pay/api');
        }

        return Mage::getSingleton('rede_pay/api');
    }


    /**
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        return $this->_helper()->getCheckoutSession();
    }


    /**
     * @return Mage_Adminhtml_Model_Session
     */
    protected function _getAdminSession()
    {
        return $this->_helper()->getAdminSession();
    }


    /**
     * @return Rede_Pay_Model_Session
     */
    protected function _getSession()
    {
        return $this->_helper()->getSession();
    }


    /**
     * @return int
     */
    protected function _getLastOrderId()
    {
        if ($this->_getCurrentOrder() && $this->_getCurrentOrder()->getId()) {
            return (int) $this->_getCurrentOrder()->getId();
        }

        $lastOrderId = $this->_getCheckoutSession()->getLastOrderId();
        if (empty($lastOrderId)) {
            $lastOrderId = $this->_getSession()->getLastOrderId();
        }

        return (int) $lastOrderId;
    }


    /**
     * @return null|Mage_Sales_Model_Order
     */
    protected function _getCurrentOrder()
    {
        return Mage::registry('current_order');
    }


    /**
     * @return Rede_Pay_Model_Logger
     */
    protected function _logger()
    {
        return $this->_helper()->getLogger();
    }


    /**
     * @return Rede_Pay_Model_Processor
     */
    protected function _processor()
    {
        return $this->_helper()->getProcessor();
    }


    /**
     * @param string $model
     *
     * @return Rede_Pay_Helper_Data
     */
    protected function _helper($model = null)
    {
        $helperName = empty($model) ? 'rede_pay' : "rede_pay/{$model}";
        return Mage::helper($helperName);
    }
    

    /**
     * @param array $consultBody
     *
     * @return null|string
     */
    protected function _getFilteredTransactionId(&$consultBody = array())
    {
        $transactionId = null;

        if (!empty($consultBody['transactionHistory'])) {
            foreach ((array) $consultBody['transactionHistory'] as $transaction) {
                $status = strtoupper(trim($transaction['status']));

                if ($this->getApi()->isStatusApproved($status)) {
                    $transactionId = $transaction['id'];
                }

                if (!$transactionId && $this->getApi()->isStatusInAnalysis($status)) {
                    $consultBody['status'] = Rede_Pay_Model_Api::STATUS_IN_ANALYSIS;
                    $transactionId         = $transaction['id'];
                }

                if (!empty($transactionId)) {
                    break;
                }
            }
        }

        return $transactionId;
    }

}
