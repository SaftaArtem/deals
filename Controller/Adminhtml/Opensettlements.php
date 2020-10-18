<?php


namespace Dealsales\Deals\Controller\Adminhtml;

use Dealsales\Deals\Model\ProcessCron;
use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

abstract class Opensettlements extends AbstractAction
{
    protected $resultPageFactory;
    protected $processCron;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ProcessCron $processCron
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->processCron = $processCron;
        parent::__construct($context);
    }
}
