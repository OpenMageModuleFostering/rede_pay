<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Block_Checkout_Denied
 */
class Rede_Pay_Block_Checkout_Denied extends Rede_Pay_Block_Checkout_Abstract
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('rede/pay/checkout/denied.phtml');
    }
    

    /**
     * @return bool
     */
    public function canRetryPayment()
    {
        return false;
    }

}
