<?php


class Neklo_Monitor_Helper_Flat extends Mage_Catalog_Helper_Product_Flat
{
    public function isEnabled($store = null)
    {
        if (Mage::registry('neklo_monitor_request')) {
            return false;
        }

        return parent::isEnabled($store);
    }
}