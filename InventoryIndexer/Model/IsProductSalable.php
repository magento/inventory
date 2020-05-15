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

/**
 * @inheritDoc
 *
 * Lightweight implementation for Storefront application.
 */
class IsProductSalable implements IsProductSalableInterface
{
    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @param GetStockItemDataInterface $stockItemData
     */
    public function __construct(GetStockItemDataInterface $getStockItemData)
    {
        $this->getStockItemData = $getStockItemData;
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
            $isSalable = false;
        }

        return $isSalable;
    }
}
