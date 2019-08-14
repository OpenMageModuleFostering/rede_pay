<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Validator_Shipping_Tracking
 */
class Rede_Pay_Model_Validator_Shipping_Tracking extends Zend_Validate_Abstract
{

    use Rede_Pay_Trait_Data;
    

    /**
     * Validates if the tracking number is according to Correios tracking numbers format.
     *
     * @param string $trackingNumber
     *
     * @return bool
     */
    public function isValid($trackingNumber)
    {
        if (!$trackingNumber) {
            return false;
        }

        $result = (bool) preg_match('/^[A-Z]{2}[0-9]{9}[A-Z]{2}$/', $trackingNumber);

        if (true === $result) {
            return true;
        }

        return false;
    }

}
