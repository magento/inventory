<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\ConfigurationOptions;

use Magento\InventoryConfiguration\Model\ResourceModel\GetConfigurationValue;
use Magento\InventoryConfigurationApi\Api\GetQtyIncrementsConfigurationValueInterface;
use Magento\InventoryConfigurationApi\Api\StockItemConfigurationInterface;

class GetQtyIncrementsConfigurationValue implements GetQtyIncrementsConfigurationValueInterface
{
    /**
     * @var GetConfigurationValue
     */
    private $getConfigurationValue;

    /**
     * @param GetConfigurationValue $getConfigurationValue
     */
    public function __construct(
        GetConfigurationValue $getConfigurationValue
    ) {
        $this->getConfigurationValue = $getConfigurationValue;
    }

    /**
     * @inheritdoc
     */
    public function forStockItem(string $sku, int $stockId): ?float
    {
        $result = $this->getConfigurationValue->execute(
            StockItemConfigurationInterface::QTY_INCREMENTS,
            $stockId,
            null,
            $sku
        );
        return (float)$result ?? $result;
    }

    /**
     * @inheritdoc
     */
    public function forStock(int $stockId): ?float
    {
        $result = $this->getConfigurationValue->execute(StockItemConfigurationInterface::QTY_INCREMENTS, $stockId);
        return (float)$result ?? $result;
    }

    /**
     * @inheritdoc
     */
    public function forGlobal(): float
    {
        return (int)$this->getConfigurationValue->execute(StockItemConfigurationInterface::QTY_INCREMENTS);
    }
}
