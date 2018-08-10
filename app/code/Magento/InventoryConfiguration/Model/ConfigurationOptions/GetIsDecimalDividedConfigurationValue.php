<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\ConfigurationOptions;

use Magento\InventoryConfiguration\Model\ResourceModel\GetConfigurationValue;
use Magento\InventoryConfigurationApi\Api\GetIsDecimalDividedConfigurationValueInterface;
use Magento\InventoryConfigurationApi\Api\StockItemConfigurationInterface;

/**
 * @inheritdoc
 */
class GetIsDecimalDividedConfigurationValue implements GetIsDecimalDividedConfigurationValueInterface
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
    public function forStockItem(string $sku, int $stockId): ?int
    {
        $result = $this->getConfigurationValue->execute(
            StockItemConfigurationInterface::IS_DECIMAL_DIVIDED,
            $stockId,
            null,
            $sku
        );
        return (int)$result ?? $result;
    }
}
