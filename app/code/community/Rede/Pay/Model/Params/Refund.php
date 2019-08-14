<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Params_Refund
 */
class Rede_Pay_Model_Params_Refund extends Rede_Pay_Model_Params_Abstract
{

    /**
     * @return $this
     */
    protected function _prepareData($force = false)
    {
        if (!$this->_validateOrder()) {
            return $this;
        }

        if ($this->_isPrepared && !$force) {
            return $this;
        }

        $this->_parameters['status'] = Rede_Pay_Model_Api::STATUS_REVERSED;

        $this->_isPrepared = true;

        return $this;
    }

}
