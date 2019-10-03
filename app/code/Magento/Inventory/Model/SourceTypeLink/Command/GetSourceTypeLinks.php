<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceTypeLink\Command;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink\CollectionFactory;
use Magento\Inventory\Model\ResourceModel\StockSourceLink\Collection as StockSourceLinkCollection;
use Magento\Inventory\Model\ResourceModel\StockSourceLink\CollectionFactory as StockSourceLinkCollectionFactory;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink\CollectionFactory as SourceTypeLinkCollectionFactory;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\InventoryApi\Api\Data\SourceTypeLinkInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkSearchResultsInterface;
use Magento\InventoryApi\Api\Data\SourceTypeLinkSearchResultsInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkSearchResultsInterfaceFactory;
use Magento\InventoryApi\Api\Data\SourceTypeLinkSearchResultsInterfaceFactory;
use Magento\InventoryApi\Api\GetSourceTypeLinksInterface;

/**
 * @inheritdoc
 */
class GetSourceTypeLinks implements GetSourceTypeLinksInterface
{
    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var StockSourceLinkCollectionFactory
     */
    private $stockSourceLinkCollectionFactory;

    /**
     * @var StockSourceLinkSearchResultsInterfaceFactory
     */
    private $stockSourceLinkSearchResultsFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    private $sourceFactory;

    private $sourceTypeLinkCollectionFactory;

    private $sourceTypeLinkSearchResultsFactory;

    /**
     * @param CollectionProcessorInterface $collectionProcessor
     * @param StockSourceLinkCollectionFactory $stockSourceLinkCollectionFactory
     * @param SourceTypeLinkCollectionFactory $sourceTypeLinkCollectionFactory
     * @param StockSourceLinkSearchResultsInterfaceFactory $stockSourceLinkSearchResultsFactory
     * @param SourceInterfaceFactory $sourceFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        CollectionProcessorInterface $collectionProcessor,
        StockSourceLinkCollectionFactory $stockSourceLinkCollectionFactory,
        SourceTypeLinkCollectionFactory $sourceTypeLinkCollectionFactory,
        StockSourceLinkSearchResultsInterfaceFactory $stockSourceLinkSearchResultsFactory,
        SourceTypeLinkSearchResultsInterfaceFactory $sourceTypeLinkSearchResultsFactory,
        SourceInterfaceFactory $sourceFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->collectionProcessor = $collectionProcessor;
        $this->stockSourceLinkCollectionFactory = $stockSourceLinkCollectionFactory;
        $this->stockSourceLinkSearchResultsFactory = $stockSourceLinkSearchResultsFactory;
        $this->sourceTypeLinkSearchResultsFactory = $sourceTypeLinkSearchResultsFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceFactory = $sourceFactory;
        $this->sourceTypeLinkCollectionFactory = $sourceTypeLinkCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(SearchCriteriaInterface $searchCriteria): SourceTypeLinkSearchResultsInterface
    {
        /** @var Collection $collection */
        $collection = $this->sourceTypeLinkCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var StockSourceLinkSearchResultsInterface $searchResult */
        $searchResult = $this->sourceTypeLinkSearchResultsFactory->create();
//
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        $searchResult->setSearchCriteria($searchCriteria);

//        return $collection->getFirstItem();
        return $searchResult;
    }
}
