<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Model\GetStockItemsDataInterface;
use Psr\Log\LoggerInterface;

/**
 * Determine the salability of multiple products in a specified stock.
 */
class AreMultipleProductsSalable
{
    /**
     * @var GetStockItemsDataInterface
     */
    private GetStockItemsDataInterface $getStockItemsData;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param GetStockItemsDataInterface $getStockItemsData
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetStockItemsDataInterface $getStockItemsData,
        LoggerInterface $logger
    ) {
        $this->getStockItemsData = $getStockItemsData;
        $this->logger = $logger;
    }

    /**
     * Define if multiple products are salable for a specified stock.
     *
     * @param array $skus
     * @param int $stockId
     * @return array
     */
    public function execute(array $skus, int $stockId): array
    {
        $isSalableResults = [];
        try {
            $stockItemsData = $this->getStockItemsData->execute($skus, $stockId);

            foreach ($stockItemsData as $sku => $stockItemData) {
                $isSalable = (bool)($stockItemData[GetStockItemsDataInterface::IS_SALABLE] ?? false);
                $isSalableResults[$sku] = $isSalable;
            }
        } catch (LocalizedException $exception) {
            $this->logger->warning(
                sprintf(
                    'Unable to fetch stock #%s data for SKUs %s. Reason: %s',
                    $stockId,
                    implode(', ', $skus),
                    $exception->getMessage()
                )
            );
            // Set all SKUs as not salable if an exception occurs
            foreach ($skus as $sku) {
                $isSalableResults[$sku] = false;
            }
        }

        return $isSalableResults;
    }
}
