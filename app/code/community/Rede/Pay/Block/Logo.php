<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Block_Logo
 *
 * @method $this setWidth(int $width)
 */
class Rede_Pay_Block_Logo extends Mage_Core_Block_Template
{

    use Rede_Pay_Trait_Data;

    const DEFAULT_LOGO_WIDTH = 180;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('rede/pay/logo.phtml');
    }


    /**
     * @return string
     */
    public function getSrc()
    {
        return $this->getSkinUrl('rede/pay/images/redepay.png');
    }


    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->__('Rede Pay');
    }


    /**
     * @return string
     */
    public function getAlt()
    {
        return $this->__('Rede Pay');
    }


    /**
     * @return int
     */
    public function getWidth()
    {
        if (!$this->getData('width')) {
            return self::DEFAULT_LOGO_WIDTH;
        }

        return $this->getData('width');
    }

}
