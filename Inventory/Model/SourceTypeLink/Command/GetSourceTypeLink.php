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
use Magento\Inventory\Model\ResourceModel\StockSourceLink\CollectionFactory as StockSourceLinkCollectionFactory;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink\CollectionFactory as SourceTypeLinkCollectionFactory;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink\Collection as SourceTypeLinkCollection;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\InventoryApi\Api\Data\SourceTypeLinkSearchResultsInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkSearchResultsInterfaceFactory;
use Magento\InventoryApi\Api\Data\SourceTypeLinkSearchResultsInterfaceFactory;
use Magento\InventoryApi\Api\GetSourceTypeLinkInterface;

/**
 * @inheritdoc
 */
class GetSourceTypeLink implements GetSourceTypeLinkInterface
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

    /**
     * @var SourceInterfaceFactory
     */
    private $sourceFactory;

    /**
     * @var SourceTypeLinkCollectionFactory
     */
    private $sourceTypeLinkCollectionFactory;

    /**
     * @var SourceTypeLinkSearchResultsInterfaceFactory
     */
    private $sourceTypeLinkSearchResultsFactory;

    /**
     * @param CollectionProcessorInterface $collectionProcessor
     * @param StockSourceLinkCollectionFactory $stockSourceLinkCollectionFactory
     * @param SourceTypeLinkCollectionFactory $sourceTypeLinkCollectionFactory
     * @param StockSourceLinkSearchResultsInterfaceFactory $stockSourceLinkSearchResultsFactory
     * @param SourceTypeLinkSearchResultsInterfaceFactory $sourceTypeLinkSearchResultsFactory
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
        /** @var SourceTypeLinkCollection $collection */
        $collection = $this->sourceTypeLinkCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var SourceTypeLinkSearchResultsInterface $searchResult */
        $searchResult = $this->sourceTypeLinkSearchResultsFactory->create();

        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        $searchResult->setSearchCriteria($searchCriteria);

        return $searchResult;
    }
}
