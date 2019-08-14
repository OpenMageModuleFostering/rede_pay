<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Block_Checkout_Pending
 */
class Rede_Pay_Block_Checkout_Pending extends Rede_Pay_Block_Checkout_Success
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('rede/pay/checkout/pending.phtml');
    }

}
