<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Params_Shipping_Tracking
 */
class Rede_Pay_Model_Params_Shipping_Tracking extends Rede_Pay_Model_Params_Abstract
{

    /**
     * @return $this
     */
    protected function _prepareData($force = false)
    {
        if (empty($this->_data['tracking_number'])) {
            return $this;
        }

        if ($this->_isPrepared && !$force) {
            return $this;
        }

        $this->_parameters['trackingNumber'] = $this->_data['tracking_number'];

        $this->_isPrepared = true;

        return $this;
    }

}
