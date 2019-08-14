<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Block_Adminhtml_System_Config_Field_Url
 */
class Rede_Pay_Block_Adminhtml_System_Config_Field_Url extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    use Rede_Pay_Trait_Data;

    /**
     * Prepares the element's html.
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        /** @var Mage_Core_Model_Config_Element $configField */
        $config    = $element->getData('field_config');
        $helperUrl = (string) $config->helper_url;

        if (empty($helperUrl)) {
            return parent::_getElementHtml($element);
        }

        list($model, $method) = explode('::', $helperUrl);

        $helper = Mage::helper(implode('/', array('rede_pay', $model)));

        if (!$helper || !method_exists($helper, $method)) {
            return parent::_getElementHtml($element);
        }

        $element->setData('value', str_replace('/index.php', '', $helper->$method()));

        return '<span class="red">' . parent::_getElementHtml($element) . '</span>';
    }

}
