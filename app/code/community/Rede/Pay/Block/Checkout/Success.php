<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Block_Checkout_Success
 */
class Rede_Pay_Block_Checkout_Success extends Rede_Pay_Block_Checkout_Abstract
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('rede/pay/checkout/success.phtml');
    }

}
