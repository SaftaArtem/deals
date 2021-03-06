<?php

namespace Dealsales\Deals\Controller\Adminhtml\Opensettlements;

use Dealsales\Deals\Controller\Adminhtml\Settlements;
use Magento\Framework\App\ResponseInterface;

class Index extends Settlements
{

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dealsales_Deals::opensettlements');
        $resultPage->getConfig()->getTitle()->prepend(__('Dealsales - Åbne afregninger'));
        return $resultPage;
    }
}
