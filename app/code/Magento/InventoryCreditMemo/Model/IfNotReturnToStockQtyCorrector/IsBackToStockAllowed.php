<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCreditMemo\Model\IfNotReturnToStockQtyCorrector;

use Magento\InventoryCatalog\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfiguration\Model\IsSourceItemsAllowedForProductTypeInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;

/**
 * Check if back to stock is allowed. Check if manage stock enable and source items allowed.
 */
class IsBackToStockAllowed
{
    /**
     * @var IsSourceItemsAllowedForProductTypeInterface
     */
    private $isSourceItemsAllowedForProductType;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @param IsSourceItemsAllowedForProductTypeInterface $isSourceItemsAllowedForProductType
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     */
    public function __construct(
        IsSourceItemsAllowedForProductTypeInterface $isSourceItemsAllowedForProductType,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        GetStockItemConfigurationInterface $getStockItemConfiguration
    ) {
        $this->isSourceItemsAllowedForProductType = $isSourceItemsAllowedForProductType;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
    }

    /**
     * @param string $sku
     * @param int $stockId
     *
     * @return bool
     */
    public function execute(string $sku, int $stockId): bool
    {
        $isAllowed = true;

        $productType = $this->getProductTypesBySkus->execute([$sku])[$sku];
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);

        if (!$stockItemConfiguration->isManageStock()
            || false === $this->isSourceItemsAllowedForProductType->execute($productType)
        ) {
            $isAllowed = false;
        }

        return $isAllowed;
    }
}
