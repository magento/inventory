<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Psr\Log\LoggerInterface;

/**
 * Lightweight implementation for Storefront application.
 */
class IsProductSalable implements IsProductSalableInterface
{
    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param GetStockItemDataInterface $getStockItemData
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetStockItemDataInterface $getStockItemData,
        LoggerInterface $logger
    ) {
        $this->getStockItemData = $getStockItemData;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        try {
            $stockItem = $this->getStockItemData->execute($sku, $stockId);
            $isSalable = (bool)($stockItem[GetStockItemDataInterface::IS_SALABLE] ?? false);
        } catch (LocalizedException $exception) {
            $this->logger->warning(
                sprintf(
                    'Unable to fetch stock #%s data for SKU %s. Reason: %s',
                    $stockId,
                    $sku,
                    $exception->getMessage()
                )
            );
            $isSalable = false;
        }

        return $isSalable;
    }
}
