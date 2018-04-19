<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * @inheritdoc
 */
class ManageStockCondition implements IsProductSalableInterface
{
    /**
     * @var StockConfigurationInterface
     */
    private $configuration;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param StockConfigurationInterface $configuration
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        StockConfigurationInterface $configuration,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        StockResolverInterface $stockResolver
    ) {
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->configuration = $configuration;
        $this->stockResolver = $stockResolver;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, SalesChannelInterface $salesChannel): bool
    {
        $stockId = (int)$this->stockResolver->get($salesChannel->getType(), $salesChannel->getCode())->getStockId();
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
        if (null === $stockItemConfiguration) {
            return false;
        }

        $globalManageStock = $this->configuration->getManageStock();
        $manageStock = false;
        if ((
                $stockItemConfiguration->isUseConfigManageStock() == 1 &&
                $globalManageStock == 1
            ) || (
                $stockItemConfiguration->isUseConfigManageStock() == 0 &&
                $stockItemConfiguration->isManageStock() == 1
            )
        ) {
            $manageStock = true;
        }

        return !$manageStock;
    }
}
