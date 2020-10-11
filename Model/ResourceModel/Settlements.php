<?php


namespace Dealsales\Deals\Model\ResourceModel;


class Settlements extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $_idFieldName = 'id';


    protected function _construct()
    {
        $this->_init('deals_settlements', 'id');
    }
}
