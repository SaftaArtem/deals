<?php

namespace Dealsales\Deals\Model\ResourceModel\Settlements\Grid;

use Dealsales\Deals\Model\ResourceModel\Settlements\Collection;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Dssettlements extends Collection implements SearchResultInterface
{
    protected $aggregations;
    private $storeManager;

    public function __construct(
        StoreManagerInterface $storeManager,
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        $model = Document::class,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        $this->storeManager = $storeManager;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);
    }

    public function getAggregations()
    {
        return $this->aggregations;
    }

    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
    }

    public function getSearchCriteria()
    {
        return null;
    }

    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    public function getTotalCount()
    {
        return $this->getSize();
    }

    public function setTotalCount($totalCount)
    {
        return $this;
    }

    public function setItems(array $items = null)
    {
        return $this;
    }

    protected function _renderFiltersBefore()
    {
        $brand_table = $this->getTable('brand');
        $this->getSelect()
            ->columns('SUM(total_price_incl_tax) as total_price_incl_tax')
            ->columns('SUM(total_price_excl_tax) as total_price_excl_tax')
            ->columns('SUM(total_commission_incl_tax) as total_commission_incl_tax')
            ->columns('SUM(total_commission_excl_tax) as total_commission_excl_tax')
            ->columns('SUM(total_price_excl_tax) as total_price_excl_tax')
            ->columns('SUM(total_price_excl_tax) as total_price_excl_tax')
            ->joinLeft(
                ['manufacturer' => $brand_table],
                'manufacturer.id = main_table.manufacturer_id',
                ['brand' => 'brand']
            )->where("type = 0")
            ->where("state = 0")
            ->group('manufacturer_id');
        parent::_renderFiltersBefore();
    }
}
