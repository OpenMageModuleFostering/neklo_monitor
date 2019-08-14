<?php

class Neklo_Monitor_InfoController extends Neklo_Monitor_Controller_Abstract
{
    public function totalAction()
    {
        /** @var Neklo_Monitor_Helper_Date $hlpDate */
        $hlpDate = Mage::helper('neklo_monitor/date');

        $fromTimestamp = $this->_getRequestHelper()->getParam('from', 0);
        $fromDate = $hlpDate->convertToString($fromTimestamp);

        $storeId = $this->_getRequestHelper()->getParam('store', null);

        // get new orders list

        /* @var $orderCollection Mage_Sales_Model_Mysql4_Order_Grid_Collection */
        $orderCollection = Mage::getResourceModel('sales/order_grid_collection');
        if ($storeId) {
            $orderCollection->addFieldToFilter('store_id', $storeId);
        }
        $orderCollection->addFieldToFilter('created_at', array('gt' => $fromDate));
        $ordersCount = $orderCollection->getSize();

        // get new customers list

        /* @var $custCollection Mage_Customer_Model_Entity_Customer_Collection */
        $custCollection = Mage::getResourceModel('customer/customer_collection');
        $orderCollection->addFieldToFilter('created_at', array('gt' => $fromDate));
        $customersCount = $custCollection->getSize();

        $result = array(
            'orders_count' => $ordersCount,
            'customers_count' => $customersCount,
        );

        $this->_jsonResult($result);
    }

    public function storeviewlistAction()
    {
        $websiteList = array();
        /* @var $website Mage_Core_Model_Website */
        foreach (Mage::app()->getWebsites() as $key => $website) {
            $groupList = array();
            foreach ($website->getGroups() as $group) {
                $storeViewList = array();
                foreach ($group->getStores() as $store) {
                    $storeViewList[] = array(
                        'store_id' => $store->getId(),
                        'name'     => $store->getName(),
                    );
                }
                $groupList[] = array(
                    'group_id' => $group->getId(),
                    'name'     => $group->getName(),
                    'store'    => $storeViewList,
                );
            }

            $websiteList[] = array(
                'website_id' => $website->getId(),
                'name'       => $website->getName(),
                'group'      => $groupList,
            );
        }
        $result = array(
            'website' => $websiteList,
        );

        $this->_jsonResult($result);
    }

    public function attrsetlistAction()
    {
        $result = array('result' => array());

        /** @var Mage_Catalog_Model_Resource_Eav_Mysql4_Product $resrc */
        $resrc = Mage::getResourceModel('catalog/product');
        /** @var Mage_Eav_Model_Mysql4_Entity_Attribute_Set_Collection $attributeSetCollection */
        $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');
        $attributeSetCollection
            ->setEntityTypeFilter($resrc->getTypeId())
            ->load();
        foreach ($attributeSetCollection as $_set) {
            /** @var Mage_Eav_Model_Entity_Attribute_Set $_set */
            $result['result'][] = array(
                'id' => $_set->getId(),
                'label' => $_set->getAttributeSetName(),
            );
        }

        $this->_jsonResult($result);
    }

}
