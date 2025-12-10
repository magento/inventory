<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\InventoryApi\Model\GetStockIdsBySkusInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStockFactory;
use Magento\InventoryIndexer\Indexer\Stock\SkuListsProcessor;
use Psr\Log\LoggerInterface;

/**
 * Reindex source items after bundle link has been saved plugin.
 */
class ReindexSourceItemsAfterSaveBundleSelectionPlugin
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
     * Reindex source items after bundle selection has been updated.
     *
     * @param ProductLinkManagementInterface $subject
     * @param bool $result
     * @param string $sku
     * @param LinkInterface $linkedProduct
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSaveChild(
        ProductLinkManagementInterface $subject,
        bool $result,
        string $sku,
        LinkInterface $linkedProduct
    ): bool {
        try {
            $stockIds = $this->getStockIdsBySkus->execute([$linkedProduct->getSku()]);
            $skuListInStockList = [];
            foreach ($stockIds as $stockId) {
                $skuListInStockList[] = $this->skuListInStockFactory->create(
                    ['stockId' => $stockId, 'skuList' => [$sku]]
                );
            }
            $this->skuListsProcessor->reindexList($skuListInStockList);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}
