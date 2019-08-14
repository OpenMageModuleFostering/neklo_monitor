<?php

class Neklo_Monitor_Helper_Data extends Mage_Core_Helper_Data
{
    public function resizeProductImage($product, $attribute)
    {
        $hlp = Mage::helper('catalog/image');
        /** @var Mage_Catalog_Helper_Image $hlp */
        $hlp->init($product, $attribute);

        return array(
            'image2xUrl' => $hlp->resize(224, 300)->__toString(),
            'image3xUrl' => $hlp->resize(336, 450)->__toString(),
        );
    }
}
