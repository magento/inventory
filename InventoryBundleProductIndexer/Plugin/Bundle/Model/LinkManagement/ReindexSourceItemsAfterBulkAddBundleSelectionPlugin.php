<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Api\ProductLinkManagementAddChildrenInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\InventoryApi\Model\GetStockIdsBySkusInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStockFactory;
use Magento\InventoryIndexer\Indexer\Stock\SkuListsProcessor;
use Psr\Log\LoggerInterface;

/**
 * Reindex source items after bundle link has been added plugin.
 */
class ReindexSourceItemsAfterBulkAddBundleSelectionPlugin
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
     * @param ProductLinkManagementAddChildrenInterface $subject
     * @param void $result
     * @param ProductInterface $product
     * @param int $optionId
     * @param LinkInterface[] $linkedProducts
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAddChildren(
        ProductLinkManagementAddChildrenInterface $subject,
        $result,
        ProductInterface $product,
        int $optionId,
        array $linkedProducts
    ): void {
        try {
            $skus = array_map(fn ($linkedProduct) => $linkedProduct->getSku(), $linkedProducts);
            $stockIds = $this->getStockIdsBySkus->execute($skus);
            $skuListInStockList = [];
            foreach ($stockIds as $stockId) {
                $skuListInStockList[] = $this->skuListInStockFactory->create(
                    ['stockId' => $stockId, 'skuList' => $skus]
                );
            }
            $this->skuListsProcessor->reindexList($skuListInStockList);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
