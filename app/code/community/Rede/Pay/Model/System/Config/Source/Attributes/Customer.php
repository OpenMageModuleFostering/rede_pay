<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_System_Config_Source_Attributes_Customer
 */
class Rede_Pay_Model_System_Config_Source_Attributes_Customer
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
            $attributes = Mage::getModel('customer/customer')->getAttributes();

            $this->_options[] = array(
                'value' => null,
                'label' => $this->_helper()->__('Select an Option...'),
            );

            foreach ($attributes as $attribute) {
                /** @var Mage_Customer_Model_Attribute $attribute */

                $attributeCode  = $attribute->getData('attribute_code');
                $attributeLabel = $attribute->getData('frontend_label');

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
