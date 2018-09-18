<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogConfiguration\Model;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;

class SaveStockItemConfiguration
{
    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @var SaveStockConfigurationInterface
     */
    private $saveStockConfiguration;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var array
     */
    private $fields = [
        StockItemConfigurationInterface::MIN_QTY => 'float',
        StockItemConfigurationInterface::STOCK_THRESHOLD_QTY => 'float',
        StockItemConfigurationInterface::MIN_SALE_QTY => 'float',
        StockItemConfigurationInterface::MAX_SALE_QTY => 'float',
        StockItemConfigurationInterface::ENABLE_QTY_INCREMENTS => 'bool',
        StockItemConfigurationInterface::QTY_INCREMENTS => 'float',
        StockItemConfigurationInterface::LOW_STOCK_DATE => 'string',
    ];

    /**
     * @param GetStockConfigurationInterface $getStockConfiguration
     * @param SaveStockConfigurationInterface $saveStockConfiguration
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        GetStockConfigurationInterface $getStockConfiguration,
        SaveStockConfigurationInterface $saveStockConfiguration,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->getStockConfiguration = $getStockConfiguration;
        $this->saveStockConfiguration = $saveStockConfiguration;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * @param string $sku
     * @param StockItemInterface $stockItem
     */
    public function execute(string $sku, StockItemInterface $stockItem): void
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem(
            $sku,
            $this->defaultStockProvider->getId()
        );
        foreach ($this->fields as $field => $type) {
            $setMethod = 'set' . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($field);
            $getMethod = 'get' . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($field);
            if ($stockItem->getData('use_config_' . $field) || $stockItem->getData('use_config_' . $field) === null) {
                $stockItemConfiguration->$setMethod(null);
            } else {
                $value = $stockItem->getData($field) !== null
                    ? $this->getValue($stockItem->getData($field), $type)
                    : $stockItemConfiguration->$getMethod();
                $stockItemConfiguration->$setMethod($value);
            }
        }

        $isQtyDecimal = $stockItem->getData(StockItemConfigurationInterface::IS_QTY_DECIMAL) !== null
            ? (bool)$stockItem->getData(StockItemConfigurationInterface::IS_QTY_DECIMAL)
            : (bool)$stockItemConfiguration->isQtyDecimal();
        $stockItemConfiguration->setIsQtyDecimal($isQtyDecimal);
        $isDecimalDivided = $stockItem->getData(StockItemConfigurationInterface::IS_DECIMAL_DIVIDED) !== null
            ? (bool)$stockItem->getData(StockItemConfigurationInterface::IS_DECIMAL_DIVIDED)
            : (bool)$stockItemConfiguration->isDecimalDivided();
        $stockItemConfiguration->setIsDecimalDivided($isDecimalDivided);

        if ($stockItem->getData('use_config_' . StockItemConfigurationInterface::MANAGE_STOCK)
            || $stockItem->getData('use_config_' . StockItemConfigurationInterface::MANAGE_STOCK) === null) {
            $stockItemConfiguration->setManageStock(null);
        } elseif (!$stockItem->getData('use_config_' . StockItemConfigurationInterface::MANAGE_STOCK)
            && empty($stockItem->getData(StockItemConfigurationInterface::MANAGE_STOCK))) {
            $stockItemConfiguration->setManageStock(false);
        } else {
            $isManageStock = $stockItem->getData(StockItemConfigurationInterface::MANAGE_STOCK) !== null
                ? (bool)$stockItem->getData(StockItemConfigurationInterface::MANAGE_STOCK)
                : (bool)$stockItemConfiguration->isManageStock();
            $stockItemConfiguration->setManageStock($isManageStock);
        }

        $this->saveStockConfiguration->forStockItem(
            $sku,
            $this->defaultStockProvider->getId(),
            $stockItemConfiguration
        );
    }

    /**
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    private function getValue($value, $type)
    {
        switch ($type) {
            case 'float':
                $value = (float)$value;
                break;
            case 'bool':
                $value = (bool)$value;
                break;
            case 'string':
                $value = (string)$value;
                break;
        }

        return $value;
    }
}
