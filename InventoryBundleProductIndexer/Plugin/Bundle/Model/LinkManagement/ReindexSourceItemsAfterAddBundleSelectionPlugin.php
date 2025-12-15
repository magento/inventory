<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\InventoryApi\Model\GetStockIdsBySkusInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStockFactory;
use Magento\InventoryIndexer\Indexer\Stock\SkuListsProcessor;
use Psr\Log\LoggerInterface;

/**
 * Reindex source items after bundle link has been added plugin.
 */
class ReindexSourceItemsAfterAddBundleSelectionPlugin
{
    /**
     * @param LoggerInterface $logger
     * @param GetStockIdsBySkusInterface $getStockIdsBySkus
     * @param SkuListInStockFactory $skuListInStockFactory
     * @param SkuListsProcessor $skuListsProcessor
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly GetStockIdsBySkusInterface $getStockIdsBySkus,
        private readonly SkuListInStockFactory $skuListInStockFactory,
        private readonly SkuListsProcessor $skuListsProcessor,
    ) {
    }

    /**
     * Reindex source items after selection has been added to bundle product.
     *
     * @param ProductLinkManagementInterface $subject
     * @param int $result
     * @param ProductInterface $product
     * @param int $optionId
     * @param LinkInterface $linkedProduct
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAddChild(
        ProductLinkManagementInterface $subject,
        int $result,
        ProductInterface $product,
        int $optionId,
        LinkInterface $linkedProduct
    ): int {
        try {
            $stockIds = $this->getStockIdsBySkus->execute([$linkedProduct->getSku()]);
            $skuListInStockList = [];
            foreach ($stockIds as $stockId) {
                $skuListInStockList[] = $this->skuListInStockFactory->create(
                    ['stockId' => $stockId, 'skuList' => [$product->getSku()]]
                );
            }
            $this->skuListsProcessor->reindexList($skuListInStockList);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}
