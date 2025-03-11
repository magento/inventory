<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\InventoryApi\Model\GetStockIdsBySkusInterface;
use Magento\InventoryBundleProductIndexer\Indexer\StockIndexer;
use Psr\Log\LoggerInterface;

/**
 * Reindex source items after bundle link has been saved plugin.
 */
class ReindexSourceItemsAfterSaveBundleSelectionPlugin
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
            $this->stockIndexer->executeList($stockIds, [$sku]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}
