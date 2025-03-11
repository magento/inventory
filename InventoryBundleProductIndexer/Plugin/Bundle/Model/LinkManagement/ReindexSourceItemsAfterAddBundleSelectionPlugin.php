<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\InventoryApi\Model\GetStockIdsBySkusInterface;
use Magento\InventoryBundleProductIndexer\Indexer\StockIndexer;
use Psr\Log\LoggerInterface;

/**
 * Reindex source items after bundle link has been added plugin.
 */
class ReindexSourceItemsAfterAddBundleSelectionPlugin
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
            $this->stockIndexer->executeList($stockIds, [$product->getSku()]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}
