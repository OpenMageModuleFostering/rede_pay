<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Params_Order
 */
class Rede_Pay_Model_Params_Order extends Rede_Pay_Model_Params_Abstract
{

    /**
     * @return $this
     */
    protected function _prepareData($force = false)
    {
        if (!$this->_validateOrder()) {
            return $this;
        }

        if ($this->_isPrepared && !$force) {
            return $this;
        }

        $this->_prepareReference()
            ->_prepareSettings()
            ->_prepareCustomer()
            ->_prepareShipping()
            ->_prepareItems()
            ->_prepareUrls();

        $this->_isPrepared = true;

        return $this;
    }


    /**
     * @return $this
     */
    protected function _prepareReference()
    {
        if (!$this->_validateOrder()) {
            return $this;
        }

        $discount = $this->getOrder()->getDiscountAmount();
        $discount = abs($discount) * 100;

        $this->_parameters = array(
            'reference' => $this->getOrder()->getRealOrderId(),
            'discount'  => $discount,
        );

        return $this;
    }


    /**
     * @return $this
     */
    protected function _prepareSettings()
    {
        if (!$this->_validateOrder()) {
            return $this;
        }

        $this->_parameters['settings'] = array(
            'expiresAt'       => $this->_getExpirationDate(),
            'attempts'        => $this->_getAttempts(),
            'maxInstallments' => $this->_getInstallments(),
        );

        return $this;
    }


    /**
     * @return $this
     */
    protected function _prepareCustomer()
    {
        if (!$this->_validateOrder()) {
            return $this;
        }

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer')->load($this->getOrder()->getCustomerId());

        /** @var Mage_Sales_Model_Order_Address $address */
        $address  = $this->getAddress();

        if (!$customer) {
            return $this;
        }

        $this->_parameters['customer'] = array(
            'name'  => $this->getOrder()->getCustomerName(),
            'email' => $this->getOrder()->getCustomerEmail(),
        );

        $taxvat = $this->_attributesHelper()->getTaxvat($customer, $address);

        if (!empty($taxvat)) {
            $this->_parameters['customer']['documents'][] = array(
                'kind'   => 'CPF',
                'number' => $taxvat
            );
        }

        /**
         * Telephone
         */
        $telephone = $this->_attributesHelper()->getTelephone($address);
        if (!empty($telephone)) {
            $this->_parameters['customer']['phones'][] = array(
                'kind'   => 'home',
                'number' => $telephone,
            );
        }

        /**
         * Cellphone
         */
        $cellphone = $this->_attributesHelper()->getCellphone($address);
        if (!empty($cellphone)) {
            $this->_parameters['customer']['phones'][] = array(
                'kind'   => 'cellphone',
                'number' => $cellphone,
            );
        }

        /**
         * Business Phone (The customer requested this to be omitted)
         */
//        $businessPhone = $this->_attributesHelper()->getBusinessPhone($address);
//        if (!empty($businessPhone)) {
//            $this->_parameters['customer']['phones'][] = array(
//                'kind'   => 'business',
//                'number' => $businessPhone,
//            );


        return $this;
    }


    /**
     * @return $this
     */
    protected function _prepareShipping()
    {
        if (!$this->_validateOrder()) {
            return $this;
        }

        /** @var Mage_Sales_Model_Order_Address $address */
        $address = $this->getAddress();

        if (!$address->getId()) {
            return $this;
        }

        $this->_parameters['shipping']['cost'] = (int) ($this->getOrder()->getShippingAmount() * 100);

        $this->_parameters['shipping']['address'] = array(
            'street'     => $this->_attributesHelper()->getAddressStreet($address),
            'number'     => $this->_attributesHelper()->getAddressNumber($address),
            'complement' => $this->_attributesHelper()->getAddressComplement($address),
            'district'   => $this->_attributesHelper()->getAddressNeighborhood($address),
            'postalCode' => $this->_attributesHelper()->getPostcode($address),
            'city'       => $address->getCity(),
            'state'      => $address->getRegionCode(),
        );

        return $this;
    }


    /**
     * @return $this
     */
    protected function _prepareItems()
    {
        if (!$this->_validateOrder()) {
            return $this;
        }

        $items = array();

        foreach ($this->getOrder()->getAllVisibleItems() as $item) {
            /** @var Mage_Sales_Model_Order_Item $item */

            // $discountAmount = (float) $item->getDiscountAmount();
            // $itemTotal      = (float) $item->getPriceInclTax() - ($discountAmount / $item->getQtyOrdered());

            /**
             * Discount here is informed as zero because it's already informed in the settings node.
             * Provide it here again would cause a duplicity and the wrong calculation of discounts to order.
             */
            $items[] = array(
                'id'          => (string) $item->getSku(),
                'description' => (string) $item->getName(),
                'amount'      => (int)    ($item->getPriceInclTax() * 100),
                'quantity'    => (int)    $item->getQtyOrdered(),
                'freight'     => (int)    0,
                'discount'    => (int)    0,
            );
        }

        $this->_parameters['items'] = $items;

        return $this;
    }


    /**
     * @return $this
     */
    protected function _prepareUrls()
    {
        if (!$this->_validateOrder()) {
            return $this;
        }

        $this->_parameters['urls'] = array(
            array(
                'kind' => 'notification',
                'url'  => $this->_helper()->getNotificationUrl($this->getOrder()->getId()),
            ),
            array(
                'kind' => 'redirect',
                'url'  => $this->_helper()->getRedirectUrl($this->getOrder()->getId()),
            ),
            array(
                'kind' => 'cancel',
                'url'  => $this->_helper()->getCancelUrl($this->getOrder()->getId()),
            ),
        );

        return $this;
    }


    /**
     * Returns the minimum of 72 hours ahead.
     *
     * @return string
     */
    protected function _getExpirationDate($hours = null)
    {
        $format  = 'Y-m-d\TH:i:s\+01:00';

        $hours = max($hours, 72);

        /** @var Mage_Core_Model_Date $dateObj */
        $dateObj = Mage::getSingleton('core/date');

        $timestamp  = $dateObj->timestamp();
        $timestamp += (60 * 60) * $hours;

        $timestamp  = date($format, $timestamp);

        return $timestamp;
    }


    /**
     * @return int
     */
    protected function _getAttempts()
    {
        return 1;
    }


    /**
     * Returns the maximum installments amount for order.
     *
     * @return int
     */
    protected function _getInstallments()
    {
        $grandTotal                = $this->getOrder()->getGrandTotal();
        $installmentsMinOrderValue = (float) max($this->_helper()->getConfigInstallmentsMinOrderValue(), 0);

        if ($installmentsMinOrderValue && ($grandTotal < $installmentsMinOrderValue)) {
            return (int) 1;
        }

        /**
         * Installments amount cannot be less than one (1).
         */
        $installmentsAmount   = max($this->_helper()->getConfigInstallmentsAmount(), 1);
        $installmentsMinValue = (float) max($this->_helper()->getConfigInstallmentsMinParcelValue(), 0);

        /**
         * Either, installments amount cannot be greater than 12.
         */
        $installmentsAmount = min(self::MAX_INSTALLMENTS_AMOUNT, $installmentsAmount);

        /**
         * If there's no configuration for minimum value for installments we return it.
         */
        if (!$installmentsMinValue) {
            return (int) $installmentsAmount;
        }

        /**
         * We need to check if current parcel value is lower than the minimum required.
         */
        $curParcelValue  = (float) ($grandTotal / $installmentsAmount);

        if ($curParcelValue < $installmentsMinValue) {
            /**
             * If so, the installments amount is updated to the maximum allowed for this purchase.
             */
            $installmentsAmount = (int) ($grandTotal / $installmentsMinValue);
        }

        return (int) max($installmentsAmount, 1);
    }

}
