<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Abstract
 */
abstract class Rede_Pay_Model_Abstract extends Mage_Core_Model_Abstract
{

    use Rede_Pay_Trait_Data;

    /**
     * @return $this
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();

        if ($this->isObjectNew()) {
            $this->setData('created_at', now());
        } else {
            $this->setData('updated_at', now());
        }

        return $this;
    }

}
