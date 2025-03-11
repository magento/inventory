<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\InventoryApi\Model\GetStockIdsBySkusInterface;
use Magento\InventoryBundleProductIndexer\Indexer\StockIndexer;
use Psr\Log\LoggerInterface;

/**
 * Reindex source items after bundle link has been removed plugin.
 */
class ReindexSourceItemsAfterRemoveBundleSelectionPlugin
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
     * Process source items after bundle selection has been removed.
     *
     * @param ProductLinkManagementInterface $subject
     * @param bool $result
     * @param string $sku
     * @param int $optionId
     * @param string $childSku
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRemoveChild(
        ProductLinkManagementInterface $subject,
        bool $result,
        string $sku,
        int $optionId,
        string $childSku
    ): bool {
        try {
            $stockIds = $this->getStockIdsBySkus->execute([$childSku]);
            $this->stockIndexer->executeList($stockIds, [$sku]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}
