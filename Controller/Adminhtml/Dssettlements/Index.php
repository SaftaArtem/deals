<?php


namespace Dealsales\Deals\Controller\Adminhtml\Dssettlements;

use Dealsales\Deals\Controller\Adminhtml\Settlements;
use Magento\Framework\App\ResponseInterface;

class Index extends Settlements
{
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dealsales_Deals::dssettlements');
        $resultPage->getConfig()->getTitle()->prepend(__('DealSales afregninger'));
        return $resultPage;
    }
}
