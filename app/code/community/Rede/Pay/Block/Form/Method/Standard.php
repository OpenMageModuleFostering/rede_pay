<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Block_Form_Method_Standard
 */
class Rede_Pay_Block_Form_Method_Standard extends Mage_Payment_Block_Form
{

    use Rede_Pay_Trait_Data;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('rede/pay/form/method/standard.phtml');
    }


    /**
     * @return string
     */
    public function getTextMessage()
    {
        $message = $this->_helper()->getConfigTextMessage();

        if (empty($message)) {
            $message = $this->__('You will proceed the payment right after the order placement.');
        }

        return $message;
    }


    /**
     * @param int $width
     *
     * @return string
     */
    public function getLogoHtml($width = null)
    {
        return $this->_helper()->getLogoHtml($width);
    }

}
