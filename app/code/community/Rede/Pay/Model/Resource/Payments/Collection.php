<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Resource_Payments_Collection
 */
class Rede_Pay_Model_Resource_Payments_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    use Rede_Pay_Trait_Data;

    protected function _construct()
    {
        $this->_init('rede_pay/payments');
        parent::_construct();
    }

}
