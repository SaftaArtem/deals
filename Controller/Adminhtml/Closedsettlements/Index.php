<?php


namespace Dealsales\Deals\Controller\Adminhtml\Closedsettlements;

use Dealsales\Deals\Controller\Adminhtml\Closedsettlements;

class Index extends Closedsettlements
{

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dealsales_Deals::index');
        $resultPage->getConfig()->getTitle()->prepend(__('Dealsales - Lukket afregninger'));
        return $resultPage;
    }
}
