<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Model\GetProductAvailableQtyInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Psr\Log\LoggerInterface;

/**
 * Service which returns available quantity of a product from stock index
 */
class GetProductAvailableQty implements GetProductAvailableQtyInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @param LoggerInterface $logger
     * @param GetStockItemDataInterface $getStockItemData
     */
    public function __construct(
        LoggerInterface $logger,
        GetStockItemDataInterface $getStockItemData
    ) {
        $this->logger = $logger;
        $this->getStockItemData = $getStockItemData;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): float
    {
        try {
            $stockItem = $this->getStockItemData->execute($sku, $stockId);
            $qty = (float) ($stockItem[GetStockItemDataInterface::QUANTITY] ?? 0);
        } catch (LocalizedException $exception) {
            $this->logger->warning(
                sprintf(
                    'Unable to fetch stock #%s data for SKU %s. Reason: %s',
                    $stockId,
                    $sku,
                    $exception->getMessage()
                )
            );
            $qty = 0;
        }

        return $qty;
    }
}
