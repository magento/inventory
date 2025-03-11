<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Ui\DataProvider;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder as SearchSearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\Inventory\Model\StockSourceLink\Command\StockSourceLinksExtensionAttributes;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\Ui\DataProvider\SearchResultFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Provider of data to stock
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StockDataProvider extends DataProvider
{
    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $apiSearchCriteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var PoolInterface
     */
    private $pool;

    /**
     * @var int|null
     */
    private $assignedSourcesLimit = null;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StockSourceLinksExtensionAttributes
     */
    private $stockSourceLinksExtensionAttributes;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchSearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param StockRepositoryInterface $stockRepository
     * @param SearchResultFactory $searchResultFactory
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param SourceRepositoryInterface $sourceRepository
     * @param SearchCriteriaBuilder $apiSearchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $pool
     * @param LoggerInterface $logger
     * @param StockSourceLinksExtensionAttributes $stockSourceLinksExtensionAttributes
     * @param int|null $assignedSourcesLimit
     * @SuppressWarnings(PHPMD.ExcessiveParameterList) All parameters are needed for backward compatibility
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchSearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        StockRepositoryInterface $stockRepository,
        SearchResultFactory $searchResultFactory,
        GetStockSourceLinksInterface $getStockSourceLinks,
        SourceRepositoryInterface $sourceRepository,
        SearchCriteriaBuilder $apiSearchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority,
        array $meta = [],
        array $data = [],
        ?PoolInterface $pool = null,
        ?LoggerInterface $logger = null,
        ?StockSourceLinksExtensionAttributes $stockSourceLinksExtensionAttributes = null,
        ?int $assignedSourcesLimit = 100
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->stockRepository = $stockRepository;
        $this->searchResultFactory = $searchResultFactory;
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->sourceRepository = $sourceRepository;
        $this->apiSearchCriteriaBuilder = $apiSearchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->pool = $pool ?: ObjectManager::getInstance()->get(PoolInterface::class);
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->stockSourceLinksExtensionAttributes = $stockSourceLinksExtensionAttributes
            ?: ObjectManager::getInstance()->get(StockSourceLinksExtensionAttributes::class);
        $this->assignedSourcesLimit = $assignedSourcesLimit;
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data = parent::getData();
        if ('inventory_stock_form_data_source' === $this->name) {
            // It is need for support of several fieldsets.
            // For details see \Magento\Ui\Component\Form::getDataSourceData
            if ($data['totalRecords'] > 0) {
                $stockId = (int)$data['items'][0][StockInterface::STOCK_ID];
                $stockGeneralData = $data['items'][0];
                $dataForSingle[$stockId] = [
                    'general' => $stockGeneralData,
                    'sources' => [
                        'assigned_sources' => $this->getAssignedSourcesData($stockId),
                    ],
                ];
                $data = $dataForSingle;
            } else {
                $data = [];
            }
        } elseif ('inventory_stock_listing_data_stock' === $this->name) {
            if ($data['totalRecords'] > 0) {
                foreach ($data['items'] as $index => $stock) {
                    $data['items'][$index]['assigned_sources'] = $this->getAssignedSourcesById($stock['stock_id']);
                }
            }
        }

        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $data = $modifier->modifyData($data);
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getSearchResult()
    {
        $searchCriteria = $this->getSearchCriteria();
        $result = $this->stockRepository->getList($searchCriteria);

        return $this->searchResultFactory->create(
            $result->getItems(),
            $result->getTotalCount(),
            $searchCriteria,
            StockInterface::STOCK_ID
        );
    }

    /**
     * Returns assigned sources Data
     *
     * @param int $stockId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getAssignedSourcesData(int $stockId): array
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField(StockSourceLinkInterface::PRIORITY)
            ->setAscendingDirection()
            ->create();
        $searchCriteria = $this->apiSearchCriteriaBuilder
            ->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId)
            ->addSortOrder($sortOrder)
            ->create();
        $this->stockSourceLinksExtensionAttributes->joinExtensionAttributes(true);
        $searchResult = $this->getStockSourceLinks->execute($searchCriteria);
        if ($searchResult->getTotalCount() === 0) {
            return [];
        }

        $stockSourceLinks = $searchResult->getItems();
        $assignedSourcesData = [];
        foreach ($stockSourceLinks as $link) {
            $assignedSourcesData[] = [
                SourceInterface::NAME => $link->getExtensionAttributes()->getSourceName(),
                StockSourceLinkInterface::SOURCE_CODE => $link->getSourceCode(),
                StockSourceLinkInterface::STOCK_ID => $link->getStockId(),
                StockSourceLinkInterface::PRIORITY => $link->getPriority(),
            ];
        }
        return $assignedSourcesData;
    }

    /**
     * Return assigned sources by id
     *
     * @param int $stockId
     * @return array
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getAssignedSourcesById(int $stockId): array
    {
        try {
            $sortOrder = $this->sortOrderBuilder
                ->setField(StockSourceLinkInterface::PRIORITY)
                ->setAscendingDirection()
                ->create();
            $searchCriteria = $this->apiSearchCriteriaBuilder
                ->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId)
                ->addSortOrder($sortOrder)
                ->setPageSize($this->assignedSourcesLimit)
                ->create();
            $searchResult = $this->getStockSourceLinks->execute($searchCriteria);
            $stockSourceLinks =  $searchResult->getItems();
            $sources = [];
            foreach ($stockSourceLinks as $link) {
                $source = $this->sourceRepository->get($link->getSourceCode());
                $sources[] = [
                    'sourceCode' => $source->getSourceCode(),
                    'name' => $source->getName()
                ];
            }
            return $sources;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new LocalizedException(__('Could not load Sources for Stock'), $e);
        }
    }
}
