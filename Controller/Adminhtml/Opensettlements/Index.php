<?php

namespace Dealsales\Deals\Controller\Adminhtml\Opensettlements;

use Dealsales\Deals\Controller\Adminhtml\Opensettlements;
use Magento\Framework\App\ResponseInterface;

class Index extends Opensettlements
{

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dealsales_Deals::opensettlements');
        $resultPage->getConfig()->getTitle()->prepend(__('Dealsales - Åbne afregninger'));
        return $resultPage;
    }
}
