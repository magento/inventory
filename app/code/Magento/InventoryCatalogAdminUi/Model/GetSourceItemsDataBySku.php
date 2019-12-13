<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Store\Model\ResourceModel\Website\Collection;

/**
 * Get source items for given product model.
 */
class GetSourceItemsDataBySku
{
    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Collection
     */
    private $websiteCollection;

    /**
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SourceRepositoryInterface $sourceRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Collection|null $websiteCollection
     * @param GetStockSourceLinksInterface|null $getStockSourceLinks
     * @param StockRepositoryInterface|null $stockRepository
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        SourceItemRepositoryInterface $sourceItemRepository,
        SourceRepositoryInterface $sourceRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Collection $websiteCollection = null,
        GetStockSourceLinksInterface $getStockSourceLinks = null,
        StockRepositoryInterface $stockRepository = null
    ) {
        $this->sourceItemRepository = $sourceItemRepository;
        $this->sourceRepository = $sourceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->websiteCollection = $websiteCollection ?: ObjectManager::getInstance()->get(Collection::class);
        $this->getStockSourceLinks = $getStockSourceLinks ?: ObjectManager::getInstance()
            ->get(GetStockSourceLinksInterface::class);
        $this->stockRepository = $stockRepository ?: ObjectManager::getInstance()->get(StockRepositoryInterface::class);
    }

    /**
     * Get source items data for given product sku.
     *
     * @param string $sku
     * @return array
     * @throws NoSuchEntityException
     */
    public function execute(string $sku): array
    {
        $sourceItemsData = [];

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        $codes = [];
        $websites = $this->websiteCollection->getItems();
        foreach ($websites as $website) {
            $codes[] = $website->getCode();
        }
        $sourcesCache = [];
        foreach ($sourceItems as $sourceItem) {
            $sourceCode = $sourceItem->getSourceCode();
            if (!isset($sourcesCache[$sourceCode])) {
                $sourcesCache[$sourceCode] = [
                    'source' => $this->sourceRepository->get($sourceCode),
                    'allowed' => $this->isSourceItemAllowed($sourceCode, $codes),
                ];
            }

            if ($sourcesCache[$sourceCode]['allowed']) {
                $source = $sourcesCache[$sourceCode];
                $sourceItemsData[] = [
                    SourceItemInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                    SourceItemInterface::QUANTITY => $sourceItem->getQuantity(),
                    SourceItemInterface::STATUS => $sourceItem->getStatus(),
                    SourceInterface::NAME => $source->getName(),
                    'source_status' => $source->isEnabled(),
                ];
            }
        }

        return $sourceItemsData;
    }

    /**
     * Verify source items is not restricted to display for admin user.
     *
     * @param string $sourceCode
     * @param array $codes
     * @return bool
     * @throws NoSuchEntityException
     */
    private function isSourceItemAllowed(string $sourceCode, array $codes): bool
    {
        $result = false;
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
                if ($salesChannel[SalesChannelInterface::TYPE] !== SalesChannelInterface::TYPE_WEBSITE
                    || ($salesChannel[SalesChannelInterface::TYPE] === SalesChannelInterface::TYPE_WEBSITE
                    && in_array($salesChannel[SalesChannelInterface::CODE], $codes))) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }
}
