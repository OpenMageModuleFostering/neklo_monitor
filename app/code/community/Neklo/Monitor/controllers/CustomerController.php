<?php

class Neklo_Monitor_CustomerController extends Neklo_Monitor_Controller_Abstract
{
    public function listAction()
    {
        /** @var Neklo_Monitor_Helper_Date $hlpDate */
        $hlpDate = Mage::helper('neklo_monitor/date');
        /** @var Neklo_Monitor_Helper_Country $hlpCountry */
        $hlpCountry = Mage::helper('neklo_monitor/country');

        /* @var $collection Mage_Customer_Model_Entity_Customer_Collection */
        $collection = Mage::getResourceModel('customer/customer_collection');
        $collection
            ->addNameToSelect()
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('created_at')
            ->addAttributeToSelect('group_id')
            ->joinAttribute('billing_postcode', 'customer_address/postcode', 'default_billing', null, 'left')
            ->joinAttribute('billing_city', 'customer_address/city', 'default_billing', null, 'left')
            ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
            ->joinAttribute('billing_region', 'customer_address/region', 'default_billing', null, 'left')
            ->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left')
            ->setOrder('created_at')
        ;

        $storeId = $this->_getRequestHelper()->getParam('store', null);
        if ($storeId) {
            $collection->addFieldToFilter('store_id', $storeId);
        }

        $queryTimestamp = (int) $this->_getRequestHelper()->getParam('query_timestamp', 0);
        $queryDate = $hlpDate->convertToString($queryTimestamp);
        if ($queryTimestamp > 0) {
            $collection->addFieldToFilter('created_at', array('lt' => $queryDate));
        }

        $offset = $this->_getRequestHelper()->getParam('offset', 0);
        $page = ceil($offset / self::PAGE_SIZE) + 1;
        $collection->setPage($page, self::PAGE_SIZE);

        $groupList = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gt' => 0))
            ->load()
            ->toOptionHash()
        ;

        $customerList = array(
            'result' => array(),
        );
        foreach ($collection as $customer) {

            if ((array_key_exists($customer->getData('group_id'), $groupList))) {
                $customerGroup = $groupList[$customer->getData('group_id')];
            } else {
                $customerGroup = 'N/A';
            }

            $customerData = array(
                'id'                => $customer->getData('entity_id'),
                'email'             => $customer->getData('email'),
                'name'              => $customer->getData('name'),
                'created_at'        => $hlpDate->convertToTimestamp($customer->getData('created_at')),
                'group'             => $customerGroup,
                'billing_country'   => $hlpCountry->getCountryName($customer->getData('billing_country_id')),
                'billing_region'    => $customer->getData('billing_region'),
                'billing_city'      => $customer->getData('billing_city'),
                'billing_postcode'  => $customer->getData('billing_postcode'),
                'billing_telephone' => $customer->getData('billing_telephone'),
            );

            $customerList['result'][] = $customerData;
        }

        // get new entities count

        if ($queryTimestamp > 0) {
            /* @var $collection Mage_Customer_Model_Entity_Customer_Collection */
            $collection = Mage::getResourceModel('customer/customer_collection');
            $collection->addFieldToFilter('created_at', array('gteq' => $queryDate));
            $customerList['new_entities_count'] = $collection->getSize();
//            $customerList['sql'] = $collection->getSelectCountSql()->__toString();
        }

        $this->_jsonResult($customerList);
    }

    public function onlineAction()
    {
        /* @var $logModel Mage_Log_Model_Visitor_Online */
        $logModel = Mage::getModel('log/visitor_online');
        $logModel->prepare();

        // get online customers list $customerIdList

        /* @var $logVisitorCustomers Mage_Log_Model_Mysql4_Visitor_Online_Collection */
        $logVisitorCustomers = $logModel->getCollection();
        $logVisitorCustomers->addFieldToFilter('customer_id', array('notnull' => true));

        $customerIdList = $logVisitorCustomers->getColumnValues('customer_id');
        $customerIdList = array_unique($customerIdList);
        $customerIdList = array_filter($customerIdList);

        // fetch online customers info $customerList

        $groupList = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gt' => 0))
            ->load()
            ->toOptionHash()
        ;

        /* @var $customerCollection Mage_Customer_Model_Entity_Customer_Collection */
        $customerCollection = Mage::getResourceModel('customer/customer_collection');
        $customerCollection
            ->addNameToSelect()
            ->addFieldToFilter('entity_id', array('in' => $customerIdList))
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('created_at')
            ->addAttributeToSelect('group_id')
            ->joinAttribute('billing_postcode', 'customer_address/postcode', 'default_billing', null, 'left')
            ->joinAttribute('billing_city', 'customer_address/city', 'default_billing', null, 'left')
            ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
            ->joinAttribute('billing_region', 'customer_address/region', 'default_billing', null, 'left')
            ->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left')
        ;

        /** @var Neklo_Monitor_Helper_Date $hlpDate */
        $hlpDate = Mage::helper('neklo_monitor/date');
        /** @var Neklo_Monitor_Helper_Country $hlpCountry */
        $hlpCountry = Mage::helper('neklo_monitor/country');

        $customerList = array();
        foreach ($customerCollection as $customer) {
            if ((array_key_exists($customer->getData('group_id'), $groupList))) {
                $customerGroup = $groupList[$customer->getData('group_id')];
            } else {
                $customerGroup = 'N/A';
            }

            $customerData = array(
                'id'                => $customer->getData('entity_id'),
                'email'             => $customer->getData('email'),
                'name'              => $customer->getData('name'),
                'created_at'        => $hlpDate->convertToTimestamp($customer->getData('created_at')),
                'group'             => $customerGroup,
                'billing_country'   => $hlpCountry->getCountryName($customer->getData('billing_country_id')),
                'billing_region'    => $customer->getData('billing_region'),
                'billing_city'      => $customer->getData('billing_city'),
                'billing_postcode'  => $customer->getData('billing_postcode'),
                'billing_telephone' => $customer->getData('billing_telephone'),
            );

            $customerList[$customer->getData('entity_id')] = $customerData;
        }

        // collect online visitors list $visitorList along with customers data

        /* @var $collection Mage_Log_Model_Mysql4_Visitor_Online_Collection */
        $collection = $logModel->getCollection();
        $collection->addFieldToFilter('last_url', array('nlike' => '%neklo_monitor%'));

        // for pages lists - load next page rows despite newly inserted rows
        $queryTimestamp = (int) $this->_getRequestHelper()->getParam('query_timestamp', 0);
        $queryDate = $hlpDate->convertToString($queryTimestamp);
        if ($queryTimestamp > 0) {
            $collection->addFieldToFilter('last_visit_at', array('lt' => $queryDate));
        }

        $offset = $this->_getRequestHelper()->getParam('offset', 0);
        $page = ceil($offset / self::PAGE_SIZE) + 1;
        $collection->setCurPage($page);
        $collection->setPageSize(self::PAGE_SIZE);

        $visitorList = array(
            'result' => array(),
        );
//        $visitorList['sql0'] = $logVisitorCustomers->getSelectSql(true);
//        $visitorList['sql1'] = $collection->getSelectSql(true);
//        $visitorList['sql2'] = $customerCollection->getSelectSql(true);
        foreach ($collection as $visitor) {
            $visitorData = array(
                'id'             => $visitor->getData('visitor_id'),
                "type"           => $visitor->getData('visitor_type'),
                "remote_addr"    => $visitor->getData('remote_addr'),
                "first_visit_at" => $hlpDate->convertToTimestamp($visitor->getData('first_visit_at')),
                "last_visit_at"  => $hlpDate->convertToTimestamp($visitor->getData('last_visit_at')),
                "last_url"       => $visitor->getData('last_url'),
            );

            $customerId = $visitor->getData('customer_id');
            if ($customerId && array_key_exists($customerId, $customerList)) {
                $visitorData['customer'] = $customerList[$customerId];
            }

            $visitorList['result'][] = $visitorData;
        }

        // get new entities count

        if ($queryTimestamp > 0) {
            /* @var $collection Mage_Log_Model_Mysql4_Visitor_Online_Collection */
            $collection = $logModel->getCollection();
            $collection->addFieldToFilter('last_url', array('nlike' => '%neklo_monitor%'));
            $collection->addFieldToFilter('last_visit_at', array('gteq' => $queryDate));
            $visitorList['new_entities_count'] = $collection->getSize();
//            $visitorList['sql_new'] = $collection->getSelectCountSql()->__toString();
        }

        $this->_jsonResult($visitorList);
    }

}
