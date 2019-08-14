<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Block_Checkout_Error
 */
class Rede_Pay_Block_Checkout_Error extends Rede_Pay_Block_Checkout_Abstract
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('rede/pay/checkout/error.phtml');
    }

}
