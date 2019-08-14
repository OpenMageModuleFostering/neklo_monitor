<?php

class Neklo_Monitor_Helper_Date extends Mage_Core_Helper_Data
{
    public function convertToTimestamp($input)
    {
        $zDate = new Zend_Date($input);
        return (int)$zDate->getTimestamp();
    }

    public function convertToString($time = null)
    {
        return date('Y-m-d H:i:s', $time);
    }
}
