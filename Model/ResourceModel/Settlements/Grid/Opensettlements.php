<?php

namespace Dealsales\Deals\Model\ResourceModel\Settlements\Grid;

use Magento\Framework\Api\Search\SearchResultInterface;

class Opensettlements extends \Dealsales\Deals\Model\ResourceModel\Settlements\Collection implements SearchResultInterface
{
    protected $aggregations;
    private $storeManager;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param string $mainTable
     * @param string $eventPrefix
     * @param string $eventObject
     * @param string $resourceModel
     * @param string $model
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|string|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        $model = \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
        $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
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
        $storeId = $this->storeManager->getStore()->getId();
        $brand_table = $this->getTable('brand');
        $collection = $this->getSelect()
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
            ->where('main_table.store_id = ' . $storeId)
            ->group('manufacturer_id');

        ci($this);
        die('--');
        $this->setCollection($collection);
        die(var_dump($collection->getSelect()->__toString()));

        parent::_renderFiltersBefore();
    }
}
