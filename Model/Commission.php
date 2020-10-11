<?php


namespace Dealsales\Deals\Model;


class Commission extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'deals_commission';

    protected $_cacheTag = 'deals_commission';

    protected $_eventPrefix = 'deals_commission';

    protected function _construct()
    {
        $this->_init('Dealsales\Deals\Model\ResourceModel\Commission');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
