<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Params_Consult
 */
class Rede_Pay_Model_Params_Consult extends Rede_Pay_Model_Params_Abstract
{

    /** @var bool */
    protected $_requireData = false;

    /**
     * @return $this
     */
    protected function _prepareData($force = false)
    {
        return $this;
    }

}
