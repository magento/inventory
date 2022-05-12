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
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;

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
     * @var GetReservationsQuantityInterface
     */
    private $getReservationsQuantity;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param GetStockItemDataInterface $getStockItemData
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetStockItemDataInterface           $getStockItemData,
        GetReservationsQuantityInterface    $getReservationsQuantity,
        LoggerInterface                     $logger
    ) {
        $this->getStockItemData             = $getStockItemData;
        $this->getReservationsQuantity      = $getReservationsQuantity;
        $this->logger                       = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        try {
            $isSalable = $this->getIsSalable($sku, $stockId);
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

    /**
     * Get isSalable status based on stock and reservations.
     *
     * @param string $sku
     * @param int $stockId
     * @return bool
     * @throws LocalizedException
     */
    private function getIsSalable(string $sku, int $stockId): bool
    {
        $stockItem = $this->getStockItemData->execute($sku, $stockId);
        $isStockSalable = (bool)($stockItem[GetStockItemDataInterface::IS_SALABLE] ?? false);

        $reservationQuantity = $this->getReservationsQuantity->execute($sku, $stockId);

        return $isStockSalable && $reservationQuantity >= 0;
    }
}
