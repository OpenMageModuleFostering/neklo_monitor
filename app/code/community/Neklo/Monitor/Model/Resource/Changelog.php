<?php


class Neklo_Monitor_Model_Resource_Changelog extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('neklo_monitor/cicl', 'cl_id');
    }

    public function fetchChangelog()
    {
        /** @var Mage_Catalog_Model_Resource_Product $resrc */
        $resrc = Mage::getResourceModel('catalog/product');
        /** @var Mage_Eav_Model_Config $eavConfig */
        $eavConfig = Mage::getSingleton('eav/config');
        /** @var Mage_Eav_Model_Entity_Attribute_Abstract $attrName */
        $attrName = $eavConfig->getCollectionAttribute($resrc->getType(), 'name');
        $attrNameTable = $attrName->getBackendTable();

        $select = $this->_getReadAdapter()->select()
            ->distinct()
            ->from(array('cl' => $this->getMainTable()), array('cl.product_id'))
            ->join(array('st' => $this->getTable('cataloginventory/stock_status')),
                'st.product_id = cl.product_id',
                array('st.qty', 'st.stock_status'))
            ->join(array('n' => $attrNameTable),
                'n.entity_id = cl.product_id',
                array('name' => 'n.value'))
            ->join(array('p' => $this->getTable('catalog/product')),
                'p.entity_id = cl.product_id',
                array('p.sku', 'p.attribute_set_id'))
            ->join(array('as' => $this->getTable('eav/attribute_set')),
                'as.attribute_set_id = p.attribute_set_id',
                array('as.attribute_set_name'))
            ->where('n.attribute_id = ?', $attrName->getId())
        ;

        $data = $this->_getReadAdapter()->fetchAssoc($select);

        $productIds = array();
        foreach($data as $_val) {
            $productIds[] = $_val['product_id'];
        }

        if ($productIds) {
            $this->_getWriteAdapter()->delete($this->getMainTable(), array('product_id IN (?)' => $productIds));
        }

        return $data;
    }

}