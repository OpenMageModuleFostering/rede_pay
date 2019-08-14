<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Block_Checkout_Payment
 */
class Rede_Pay_Block_Checkout_Payment extends Rede_Pay_Block_Checkout_Abstract
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('rede/pay/checkout/payment.phtml');
    }

}
