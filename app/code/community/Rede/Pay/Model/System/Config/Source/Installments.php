<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_System_Config_Source_Installments
 */
class Rede_Pay_Model_System_Config_Source_Installments
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
            for ($y = 1; $y <= 12; $y++) {
                $this->_options[] = array(
                    'value' => $y,
                    'label' => $y,
                );
            }
        }

        return $this->_options;
    }

}
