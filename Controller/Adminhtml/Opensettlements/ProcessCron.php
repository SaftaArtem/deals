<?php


namespace Dealsales\Deals\Controller\Adminhtml\Opensettlements;

use Dealsales\Deals\Controller\Adminhtml\Opensettlements;

class ProcessCron extends Opensettlements
{
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store_id', 0);
        $this->processCron->proccessSettlements($storeId, true);
        //$this->_redirect('*/*/index');
    }
}
