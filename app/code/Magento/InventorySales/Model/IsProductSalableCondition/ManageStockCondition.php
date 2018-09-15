<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetInventoryConfigurationInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

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
     * @var GetInventoryConfigurationInterface
     */
    private $getInventoryConfiguration;

    /**
     * @param StockConfigurationInterface $configuration
     * @param GetInventoryConfigurationInterface $getInventoryConfiguration
     */
    public function __construct(
        StockConfigurationInterface $configuration,
        GetInventoryConfigurationInterface $getInventoryConfiguration
    ) {
        $this->configuration = $configuration;
        $this->getInventoryConfiguration = $getInventoryConfiguration;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        return $this->getInventoryConfiguration->isManageStock($sku, $stockId);
    }
}
