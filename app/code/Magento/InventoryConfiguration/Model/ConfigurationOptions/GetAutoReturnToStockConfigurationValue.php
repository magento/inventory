<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\ConfigurationOptions;

use Magento\InventoryConfiguration\Model\ResourceModel\GetConfigurationValue;
use Magento\InventoryConfigurationApi\Api\GetAutoReturnToStockConfigurationValueInterface;
use Magento\InventoryConfigurationApi\Api\StockItemConfigurationInterface;

class GetAutoReturnToStockConfigurationValue implements GetAutoReturnToStockConfigurationValueInterface
{
    const CONFIG_OPTION = 'auto_return_to_stock';

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
    public function forStock(int $stockId): ?int
    {
        $result = $this->getConfigurationValue->execute(self::CONFIG_OPTION, $stockId);
        return isset($result) ? (int)$result : $result;
    }

    /**
     * @inheritdoc
     */
    public function forGlobal(): int
    {
        return (int)$this->getConfigurationValue->execute(self::CONFIG_OPTION);
    }
}
