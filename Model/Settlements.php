<?php

namespace Dealsales\Deals\Model;

class Settlements extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'deals_settlements';

    protected $_cacheTag = 'deals_settlements';

    protected $_eventPrefix = 'deals_settlements';

    protected function _construct()
    {
        $this->_init('Dealsales\Deals\Model\ResourceModel\Settlements');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
