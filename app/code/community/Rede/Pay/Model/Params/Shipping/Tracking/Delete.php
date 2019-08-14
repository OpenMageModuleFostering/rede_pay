<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Params_Shipping_Tracking_Delete
 */
class Rede_Pay_Model_Params_Shipping_Tracking_Delete extends Rede_Pay_Model_Params_Abstract
{

    /**
     * @return $this
     */
    protected function _prepareData($force = false)
    {
        if ($this->_isPrepared && !$force) {
            return $this;
        }

        $this->_parameters['reason'] = 'BAD_TRACKING_NUMBER';

        $this->_isPrepared = true;

        return $this;
    }

}
