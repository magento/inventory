<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\ConfigurationOptions;

use Magento\InventoryConfiguration\Model\ResourceModel\GetConfigurationValue;
use Magento\InventoryConfigurationApi\Api\GetBackordersConfigurationValueInterface;
use Magento\InventoryConfigurationApi\Api\StockItemConfigurationInterface;

class GetBackordersConfigurationValue implements GetBackordersConfigurationValueInterface
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
    public function forSourceItem(string $sku, string $sourceCode): ?int
    {
        $result = $this->getConfigurationValue->execute(
            StockItemConfigurationInterface::BACKORDERS,
            null,
            $sourceCode,
            $sku
        );
        return isset($result) ? (int)$result : $result;
    }

    /**
     * @inheritdoc
     */
    public function forSource(string $sourceCode): ?int
    {
        $result = $this->getConfigurationValue->execute(
            StockItemConfigurationInterface::BACKORDERS,
            null,
            $sourceCode
        );
        return isset($result) ? (int)$result : $result;
    }

    /**
     * @inheritdoc
     */
    public function forGlobal(): int
    {
        return (int)$this->getConfigurationValue->execute(StockItemConfigurationInterface::BACKORDERS);
    }
}
