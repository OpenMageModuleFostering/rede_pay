<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Model_Validator_Cpf
 */
class Rede_Pay_Model_Validator_Cpf extends Zend_Validate_Abstract
{

    use Rede_Pay_Trait_Data;

    /**
     * @param string $data
     *
     * @return bool
     */
    public function isValid($cpf)
    {
        if (empty($cpf)) {
            return false;
        }

        $cpf = preg_replace('[^0-9]', null, $cpf);
        $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);

        if (strlen($cpf) != 11) {
            return false;
        }

        if (
            $cpf == '00000000000' || $cpf == '11111111111' || $cpf == '22222222222' || $cpf == '33333333333' ||
            $cpf == '44444444444' || $cpf == '55555555555' || $cpf == '66666666666' || $cpf == '77777777777' ||
            $cpf == '88888888888' || $cpf == '99999999999'
        ) {
            return false;

        }

        for ($t = 9; $t < 11; $t++) {

            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf{$c} * (($t + 1) - $c);
            }

            $d = ((10 * $d) % 11) % 10;

            if ($cpf{$c} != $d) {
                return false;
            }
        }

        return true;
    }

}
