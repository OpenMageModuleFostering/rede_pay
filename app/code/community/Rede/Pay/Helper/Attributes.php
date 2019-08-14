<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Helper_Attributes
 */
class Rede_Pay_Helper_Attributes extends Rede_Pay_Helper_Data
{

    /**
     * @return string
     */
    public function getConfigAttributesTaxvat()
    {
        return $this->getPaymentConfig('attributes_taxvat');
    }


    /**
     * @return string
     */
    public function getConfigAttributesTelephone()
    {
        return $this->getPaymentConfig('attributes_telephone');
    }


    /**
     * @return string
     */
    public function getConfigAttributesCellphone()
    {
        return $this->getPaymentConfig('attributes_cellphone');
    }


    /**
     * @return string
     */
    public function getConfigAttributesBusinessPhone()
    {
        return $this->getPaymentConfig('attributes_business_phone');
    }


    /**
     * @return string
     */
    public function getConfigAttributesAddressStreet()
    {
        return $this->getPaymentConfig('attributes_street');
    }


    /**
     * @return string
     */
    public function getConfigAttributesAddressNumber()
    {
        return $this->getPaymentConfig('attributes_number');
    }


    /**
     * @return string
     */
    public function getConfigAttributesAddressComplement()
    {
        return $this->getPaymentConfig('attributes_complement');
    }


    /**
     * @return string
     */
    public function getConfigAttributesAddressNeighborhood()
    {
        return $this->getPaymentConfig('attributes_neighborhood');
    }


    /**
     * @param string                   $attributeCode
     * @param Mage_Core_Model_Abstract $object
     *
     * @return null|string
     */
    public function extractEavAttributeValue($attributeCode = null, Mage_Core_Model_Abstract $object)
    {
        if (!empty($attributeCode) && $object->hasData($attributeCode)) {
            return $object->getData($attributeCode);
        }

        return null;
    }


    /**
     * @param Mage_Sales_Model_Order_Address $address
     *
     * @return null|string
     */
    public function getAddressStreet(Mage_Sales_Model_Order_Address $address)
    {
        /** @var string $code */
        $code = $this->getConfigAttributesAddressStreet();

        if (empty($code)) {
            return null;
        }

        if ($data = $this->_getStreetLine($code, $address)) {
            return $data;
        }

        return $this->extractEavAttributeValue($code, $address);
    }


    /**
     * @param Mage_Sales_Model_Order_Address $address
     *
     * @return null|string
     */
    public function getAddressNumber(Mage_Sales_Model_Order_Address $address)
    {
        /** @var string $code */
        $code = $this->getConfigAttributesAddressNumber();

        if (empty($code)) {
            return null;
        }

        if ($data = $this->_getStreetLine($code, $address)) {
            return $data;
        }

        return $this->extractEavAttributeValue($code, $address);
    }


    /**
     * @param Mage_Sales_Model_Order_Address $address
     *
     * @return null|string
     */
    public function getAddressComplement(Mage_Sales_Model_Order_Address $address)
    {
        /** @var string $code */
        $code = $this->getConfigAttributesAddressComplement();

        if (empty($code)) {
            return null;
        }

        if ($data = $this->_getStreetLine($code, $address)) {
            return $data;
        }

        return $this->extractEavAttributeValue($code, $address);
    }


    /**
     * @param Mage_Sales_Model_Order_Address $address
     *
     * @return null|string
     */
    public function getAddressNeighborhood(Mage_Sales_Model_Order_Address $address)
    {
        /** @var string $code */
        $code = $this->getConfigAttributesAddressNeighborhood();

        if (empty($code)) {
            return null;
        }

        if ($data = $this->_getStreetLine($code, $address)) {
            return $data;
        }

        return $this->extractEavAttributeValue($code, $address);
    }


    /**
     * @param Mage_Customer_Model_Customer   $customer
     * @param Mage_Sales_Model_Order_Address $address
     *
     * @return null|string
     */
    public function getTaxvat(Mage_Customer_Model_Customer $customer, Mage_Sales_Model_Order_Address $address)
    {
        $taxvat = $this->extractEavAttributeValue($this->getConfigAttributesTaxvat(), $customer);

        if (empty($taxvat)) {
            $taxvat = $this->extractEavAttributeValue($this->getConfigAttributesTaxvat(), $address);
        }

        if (empty($taxvat)) {
            $taxvat = $address->getOrder()->getCustomerTaxvat();
        }

        $taxvat = $this->_removeNonNumbers($taxvat);

        return $taxvat;
    }


    public function getPostcode(Mage_Sales_Model_Order_Address $address)
    {
        $postcode = $address->getPostcode();
        return $this->_preparePostcode($postcode);
    }


    /**
     * @param Mage_Sales_Model_Order_Address $address
     *
     * @return null|string
     */
    public function getTelephone(Mage_Sales_Model_Order_Address $address)
    {
        $number = $this->extractEavAttributeValue($this->getConfigAttributesTelephone(), $address);
        return $this->_preparePhoneNumber($number);
    }


    /**
     * @param Mage_Sales_Model_Order_Address $address
     *
     * @return null|string
     */
    public function getCellphone(Mage_Sales_Model_Order_Address $address)
    {
        $number = $this->extractEavAttributeValue($this->getConfigAttributesCellphone(), $address);
        return $this->_preparePhoneNumber($number);
    }


    /**
     * @param Mage_Sales_Model_Order_Address $address
     *
     * @return null|string
     */
    public function getBusinessPhone(Mage_Sales_Model_Order_Address $address)
    {
        $number = $this->extractEavAttributeValue($this->getConfigAttributesBusinessPhone(), $address);
        return $this->_preparePhoneNumber($number);
    }


    /**
     * @param string $number
     *
     * @return mixed|string
     */
    protected function _preparePhoneNumber(&$number = null)
    {
        $number = $this->_removeNonNumbers($number);

        if (!$this->isValidPhoneNumber($number)) {
            return null;
        }

        $startAt = max((strlen($number) - 11), 0);
        $number = substr($number, $startAt, 11);

        if (strlen($number) < 10) {
            $number = str_pad($number, 10, '0', STR_PAD_LEFT);
        }

        return $number;
    }


    /**
     * @param string $postcode
     *
     * @return mixed|string
     */
    protected function _preparePostcode(&$postcode = null)
    {
        $postcode = $this->_removeNonNumbers($postcode);

        if (empty($postcode)) {
            Mage::throwException($this->__('The postcode number cannot be empty. Please insert a correct value.'));
        }

        if (strlen($postcode) < 8) {
            $postcode = str_pad($postcode, 8, '0', STR_PAD_LEFT);
        }

        return $postcode;
    }


    /**
     * @param string $data
     *
     * @return int
     */
    protected function _removeNonNumbers(&$data = null)
    {
        $data = preg_replace('/[^0-9]/', null, $data);
        return $data;
    }


    /**
     * @param string $number
     *
     * @return bool
     */
    public function isValidPhoneNumber(&$number = null)
    {
        $result = true;

        if (empty($number)) {
            $result = false;
        }

        if (strlen($number) < 8) {
            false;
        }

        return (bool) $result;
    }


    /**
     * @param string                         $code
     * @param Mage_Sales_Model_Order_Address $address
     *
     * @return null|string
     */
    protected function _getStreetLine($code, Mage_Sales_Model_Order_Address $address)
    {
        if (strpos($code, 'street_') !== false) {
            $split = explode('_', $code);

            if (isset($split[1])) {
                return $address->getStreet((int) $split[1]);
            }
        }

        return null;
    }

}
