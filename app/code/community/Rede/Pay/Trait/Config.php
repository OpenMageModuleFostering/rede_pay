<?php
/**
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 *
 * Class Rede_Pay_Trait_Data
 */
trait Rede_Pay_Trait_Config
{

    /**
     * @return string
     */
    public function getSecretApiKey()
    {
        return $this->_helper()->getConfigSecretApiKey();
    }


    /**
     * @return string
     */
    public function getPublishableApiKey()
    {
        return $this->_helper()->getConfigPublishableApiKey();
    }


    /**
     * @return string
     */
    public function getRedePayScriptUrl()
    {
        return $this->_helper()->getConfigRedePayScriptUrl();
    }


    /**
     * @return int
     */
    public function getLightboxDelaySeconds()
    {
        return (int) $this->_helper()->getLightboxDelaySeconds();
    }


    /**
     * @return int
     */
    public function getLightboxDelayMiliseconds()
    {
        return (int) $this->_helper()->getLightboxDelayMiliseconds();
    }

}
