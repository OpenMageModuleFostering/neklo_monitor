<?php

class Neklo_Monitor_Helper_Product extends Mage_Core_Helper_Data
{
    // TODO: clean
    /**
     * @param null $storeId
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function getProductsOutofstockCollection($storeId = null)
    {
        /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        if ($storeId) {
            $collection->addStoreFilter($storeId);
        }

        $collection->addAttributeToFilter(
            'status',
            array('in' => Mage::getSingleton('catalog/product_status')->getSaleableStatusIds())
        );

        // copy-pasted from CE 1.4 Layer Model
        /*
        $attributes = Mage::getSingleton('catalog/config')->getProductAttributes();
        $collection->addAttributeToSelect($attributes)
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
        ;
        */

        $collection->addAttributeToSelect(
            array(
                'name',
                'price',
                'small_image', // exists in collection when Flat Product is enabled
            )
        );
        $collection
            ->joinField(
                'is_in_stock',
                'cataloginventory/stock_item',
                'is_in_stock',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            )
            ->addAttributeToFilter('is_in_stock', 0)
            // TODO: investigate qty = 0
//            ->joinField(
//                'qty',
//                'cataloginventory/stock_item',
//                'qty',
//                'product_id=entity_id',
//                '{{table}}.stock_id=1',
//                'left'
//            )
//            ->addAttributeToFilter('qty', array('eq' => 0))
        ;
        return $collection;
    }
}