<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Api\ProductLinkManagementAddChildrenInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\InventoryApi\Model\GetStockIdsBySkusInterface;
use Magento\InventoryBundleProductIndexer\Indexer\StockIndexer;
use Psr\Log\LoggerInterface;

/**
 * Reindex source items after bundle link has been added plugin.
 */
class ReindexSourceItemsAfterBulkAddBundleSelectionPlugin
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GetStockIdsBySkusInterface
     */
    private $getStockIdsBySkus;

    /**
     * @var StockIndexer
     */
    private $stockIndexer;

    /**
     * @param LoggerInterface $logger
     * @param GetStockIdsBySkusInterface $getStockIdsBySkus
     * @param StockIndexer $stockIndexer
     */
    public function __construct(
        LoggerInterface $logger,
        GetStockIdsBySkusInterface $getStockIdsBySkus,
        StockIndexer $stockIndexer
    ) {
        $this->logger = $logger;
        $this->getStockIdsBySkus = $getStockIdsBySkus;
        $this->stockIndexer = $stockIndexer;
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
            $this->stockIndexer->executeList($stockIds, [$product->getSku()]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
