<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableForStockCondition;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForStockInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * @inheritdoc
 */
class BackOrderCondition implements IsProductSalableForStockInterface
{
    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        StockResolverInterface $stockResolver
    ) {
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->stockResolver = $stockResolver;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
        if (null === $stockItemConfiguration) {
            return false;
        }

        return $stockItemConfiguration->getBackorders() !== StockItemConfigurationInterface::BACKORDERS_NO;
    }
}
