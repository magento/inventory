<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryApi\Model\IsSourceAllowedForUserInterface;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory;

/**
 * @inheritDoc
 */
class IsSourceAllowedForUser implements IsSourceAllowedForUserInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CollectionFactory
     */
    private $websiteCollectionFactory;

    /**
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionFactory $websiteCollection
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param StockRepositoryInterface $stockRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionFactory $websiteCollection,
        GetStockSourceLinksInterface $getStockSourceLinks,
        StockRepositoryInterface $stockRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->websiteCollectionFactory = $websiteCollection;
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->stockRepository = $stockRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $sourceCode): bool
    {
        $codes = $this->getWebsitesCodes();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(StockSourceLinkInterface::SOURCE_CODE, $sourceCode)
            ->create();
        $salesChannels = [];
        foreach ($this->getStockSourceLinks->execute($searchCriteria)->getItems() as $link) {
            if (!isset($salesChannels[$link->getStockId()])) {
                $stock = $this->stockRepository->get((int)$link->getStockId());
                $salesChannels[$stock->getStockId()] = $stock->getExtensionAttributes()->getSalesChannels();
            }
        }
        foreach ($salesChannels as $stockSalesChannels) {
            foreach ($stockSalesChannels as $salesChannel) {
                if ($salesChannel->getType() === 'website' && !in_array($salesChannel->getCode(), $codes)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get available website codes.
     *
     * @return array
     */
    private function getWebsitesCodes(): array
    {
        $codes = [];
        $websiteCollection = $this->websiteCollectionFactory->create();
        $websites = $websiteCollection->getItems();
        foreach ($websites as $website) {
            $codes[] = $website->getCode();
        }

        return $codes;
    }
}
