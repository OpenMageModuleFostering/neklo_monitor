<?php

class Neklo_Monitor_ProductController extends Neklo_Monitor_Controller_Abstract
{
    public function outofstockAction()
    {
        $storeId = $this->_getRequestHelper()->getParam('store', null);

        /** @var Neklo_Monitor_Model_Minfo_Parser $parser */
        $parser = Mage::getModel('neklo_monitor/minfo_parser');
        $collection = $parser->getProductsOutofstockCollection($storeId);

        $outOfStockProductList = array();
        $hlp = Mage::helper('catalog/image');
        /** @var Mage_Catalog_Helper_Image $hlp */
        foreach ($collection as $row) {
            /** @var Mage_Catalog_Model_Product $row */
            $hlp->init($row, 'small_image');
            $outOfStockProductList[] = array(
                'id'    => $row->getEntityId(),
                'name'  => $row->getName(),
                'price' => Mage::app()->getStore($storeId)->convertPrice($row->getPrice(), true, false),
                'sku'   => $row->getSku(),
                'image2xUrl' => $hlp->resize(224, 300)->__toString(),
                'image3xUrl' => $hlp->resize(336, 450)->__toString(),
            );
        }

        $this->_jsonResult($outOfStockProductList);
    }

}
