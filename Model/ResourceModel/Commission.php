<?php


namespace Dealsales\Deals\Model\ResourceModel;


class Commission extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('deals_commission', 'id');
    }
}
