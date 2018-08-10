<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\ConfigurationOptions;

use Magento\InventoryConfiguration\Model\ResourceModel\GetConfigurationValue;
use Magento\InventoryConfigurationApi\Api\GetStockThresholdQtyConfigurationValueInterface;
use Magento\InventoryConfigurationApi\Api\StockItemConfigurationInterface;

class GetStockThresholdQtyConfigurationValue implements GetStockThresholdQtyConfigurationValueInterface
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
    public function forStock(int $stockId): ?float
    {
        $result = $this->getConfigurationValue->execute(StockItemConfigurationInterface::STOCK_THRESHOLD_QTY, $stockId);
        return (float)$result ?? $result;
    }

    /**
     * @inheritdoc
     */
    public function forGlobal(): float
    {
        return (float)$this->getConfigurationValue->execute(StockItemConfigurationInterface::STOCK_THRESHOLD_QTY);
    }
}
