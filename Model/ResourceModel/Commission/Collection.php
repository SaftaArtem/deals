<?php


namespace Dealsales\Deals\Model\ResourceModel\Commission;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init('Dealsales\Deals\Model\Commission', 'Dealsales\Deals\Model\ResourceModel\Commission');
    }
}
