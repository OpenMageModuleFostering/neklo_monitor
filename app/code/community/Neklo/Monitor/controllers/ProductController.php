<?php

class Neklo_Monitor_ProductController extends Neklo_Monitor_Controller_Abstract
{
    public function outofstockAction()
    {
        $storeId = $this->_getRequestHelper()->getParam('store', null);

        /** @var Neklo_Monitor_Model_Minfo_Parser $parser */
        $parser = Mage::getModel('neklo_monitor/minfo_parser');
        $collection = $parser->getProductsOutofstockCollection($storeId);

        $offset = $this->_getRequestHelper()->getParam('offset', 0);
        $page = ceil($offset / self::PAGE_SIZE) + 1;
        $collection->setPage($page, self::PAGE_SIZE);

        $collection->setOrder('main_table.created_at', 'desc');

        $outOfStockProductList = array('result' => array());
        foreach ($collection as $row) {
            /** @var Mage_Catalog_Model_Product $row */
            $listItem = array(
                'id'    => $row->getEntityId(),
                'name'  => $row->getName(),
                'price' => Mage::app()->getStore($storeId)->convertPrice($row->getPrice(), true, false),
                'sku'   => $row->getSku(),
            );
            $listItem += Mage::helper('neklo_monitor')->resizeProductImage($row, 'small_image');
            $outOfStockProductList['result'][] = $listItem;
        }

        $this->_jsonResult($outOfStockProductList);
    }

}
