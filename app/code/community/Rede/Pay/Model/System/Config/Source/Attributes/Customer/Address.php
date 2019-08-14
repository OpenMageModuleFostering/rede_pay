<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_System_Config_Source_Attributes_Customer_Address
 */
class Rede_Pay_Model_System_Config_Source_Attributes_Customer_Address
{

    use Rede_Pay_Trait_Data;

    /** @var array */
    protected $_options = array();

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (empty($this->_options)) {

            /** @var array $attributes */
            $attributes = Mage::getModel('customer/address')->getAttributes();

            $this->_options[] = array(
                'value' => null,
                'label' => $this->_helper()->__('Select an Option...'),
            );

            for ($y = 1; $y <= 4; $y++) {
                $this->_options[] = array(
                    'value' => 'street_' . $y,
                    'label' => $this->_helper()->__('Street Address Line %d', $y),
                );
            }

            foreach ($attributes as $attribute) {
                /** @var Mage_Customer_Model_Attribute $attribute */

                $attributeCode  = $attribute->getData('attribute_code');
                $attributeLabel = $attribute->getData('frontend_label');

                if ($attributeCode == 'street') {
                    continue;
                }

                if (empty($attributeCode) || empty($attributeLabel)) {
                    continue;
                }

                $this->_options[] = array(
                    'value' => $attributeCode,
                    'label' => $attributeLabel,
                );
            }
        }

        return $this->_options;
    }

}
