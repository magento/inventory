<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\ConfigurationOptions;

use Magento\InventoryConfiguration\Model\ResourceModel\GetConfigurationValue;
use Magento\InventoryConfigurationApi\Api\GetNotifyStockQtyConfigurationValueInterface;
use Magento\InventoryConfigurationApi\Api\StockItemConfigurationInterface;

class GetNotifyStockQtyConfigurationValue implements GetNotifyStockQtyConfigurationValueInterface
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
    public function forSourceItem(string $sku, string $sourceCode): ?float
    {
        $result = $this->getConfigurationValue->execute(
            StockItemConfigurationInterface::NOTIFY_STOCK_QTY,
            null,
            $sourceCode,
            $sku
        );
        return (float)$result ?? $result;
    }

    /**
     * @inheritdoc
     */
    public function forSource(string $sourceCode): ?float
    {
        $result = $this->getConfigurationValue->execute(
            StockItemConfigurationInterface::NOTIFY_STOCK_QTY,
            null,
            $sourceCode
        );
        return (float)$result ?? $result;
    }

    /**
     * @inheritdoc
     */
    public function forGlobal(): float
    {
        return (int)$this->getConfigurationValue->execute(StockItemConfigurationInterface::NOTIFY_STOCK_QTY);
    }
}
