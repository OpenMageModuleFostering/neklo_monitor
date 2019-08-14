<?php

class Neklo_Monitor_State_IndexerController extends Neklo_Monitor_Controller_Abstract
{
    public function listAction()
    {
        // get indexers list and their statuses

        $indexers = array();
        /** @var Mage_Index_Model_Mysql4_Process_Collection $collection */
        $collection = Mage::getResourceModel('index/process_collection');

        foreach ($collection as $key => $item) {
            /** @var Mage_Index_Model_Process $item */
            if (method_exists($item->getIndexer(), 'isVisible')) { // Older Magento versions do not have this method
                if (!$item->getIndexer()->isVisible()) {
                    $collection->removeItemByKey($key);
                    continue;
                }
            }
            if ($item->isLocked()) {
                $item->setStatus(Mage_Index_Model_Process::STATUS_RUNNING);
            }
            $indexer = array(
                'id' => $item->getData('indexer_code'),
                'label' => $item->getIndexer()->getName(),
                'status' => $item->getStatus(), // (Mage_Index_Model_Process::STATUS_PENDING || STATUS_RUNNING || STATUS_REQUIRE_REINDEX)
                'update_required' => 0,
            );
            if (method_exists($item, 'getUnprocessedEventsCollection')) { // Older Magento versions do not have this method
                $indexer['update_required'] = ($item->getUnprocessedEventsCollection()->count() > 0 ? 1 : 0); // (0 || 1)
            }
            $indexers[] = $indexer;
        }

        $result = array(
            'result' => $indexers,
        );

        $this->_jsonResult($result);
    }

    public function reindexAction()
    {
        // TODO
    }

    public function reindexallAction()
    {
        // TODO
    }

}