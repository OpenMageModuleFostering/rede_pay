<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Block_Adminhtml_System_Config_Fieldset_Group
 */
class Rede_Pay_Block_Adminhtml_System_Config_Fieldset_Group
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{

    use Rede_Pay_Trait_Data;

    /**
     * Return header comment part of html for fieldset
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getHeaderCommentHtml($element)
    {
        $groupConfig = $this->getGroup($element)->asArray();

        $url     = $groupConfig['register_url'];
        $imgSrc  = $this->getSkinUrl('rede/pay/images/bt_cadastro.png');
        $logoSrc = $this->getSkinUrl('rede/pay/images/redepay.png');

        if (empty($url) || !$element->getComment()) {
            return parent::_getHeaderCommentHtml($element);
        }

        $html  = '<div class="comment">';
        $html .= '<p><img src="'.$logoSrc.'" width="250"/><p/>';
        $html .= '<p>' . $element->getComment() . '<p/>';
        $html .= '<p><a target="_blank" href="'.$url.'"><img src="'.$imgSrc.'"/></a></p>';
        $html .= '</div>';
        $html .= '<br/>';

        return $html;
    }

}
