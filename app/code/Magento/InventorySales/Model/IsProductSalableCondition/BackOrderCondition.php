<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\InventoryConfigurationApi\Api\GetInventoryConfigurationInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;

/**
 * @inheritdoc
 */
class BackOrderCondition implements IsProductSalableInterface
{
    /**
     * @var GetInventoryConfigurationInterface
     */
    private $getInventoryConfiguration;

    /**
     * @param GetInventoryConfigurationInterface $getInventoryConfiguration
     */
    public function __construct(
        GetInventoryConfigurationInterface $getInventoryConfiguration
    ) {
        $this->getInventoryConfiguration = $getInventoryConfiguration;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $backorders = $this->getInventoryConfiguration->getBackorders($sku, $stockId);
        $minQty = $this->getInventoryConfiguration->getMinQty($sku, $stockId);
        if ($backorders !== SourceItemConfigurationInterface::BACKORDERS_NO && $minQty >= 0) {
            return true;
        }

        return false;
    }
}
