<?php

class Neklo_Monitor_Helper_Country extends Mage_Core_Helper_Data
{
    protected $_countryList = array();

    public function getCountryName($countryCode)
    {
        if (!array_key_exists($countryCode, $this->_countryList)) {
            $countryName = Mage::app()->getLocale()->getCountryTranslation($countryCode);
            if ($countryName) {
                $this->_countryList[$countryCode] = $countryName;
            } else {
                $this->_countryList[$countryCode] = null;
            }
        }
        return $this->_countryList[$countryCode];
    }
}
