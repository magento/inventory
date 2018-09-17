<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Plugin\Import;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\CatalogImportExport\Model\StockItemImporterInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;

/**
 * Save stock item configuration for given product and default stock after stock item was saved successfully.
 */
class StockItemConfigurationImporterPlugin
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
     * @var StockItemInterfaceFactory
     */
    private $stockItemFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var array
     */
    private $fields = [
        StockItemConfigurationInterface::MANAGE_STOCK => 'bool',
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
     * @param StockItemInterfaceFactory $stockItemFactory
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        GetStockConfigurationInterface $getStockConfiguration,
        SaveStockConfigurationInterface $saveStockConfiguration,
        DefaultStockProviderInterface $defaultStockProvider,
        StockItemInterfaceFactory $stockItemFactory,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->getStockConfiguration = $getStockConfiguration;
        $this->saveStockConfiguration = $saveStockConfiguration;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->stockItemFactory = $stockItemFactory;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * After plugin Import to import Stock Data to Source Items
     *
     * @param StockItemImporterInterface $subject
     * @param null $result
     * @param array $stockData
     * @return void
     * @see StockItemImporterInterface::import()
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterImport(
        StockItemImporterInterface $subject,
        $result,
        array $stockData
    ) {
        foreach ($stockData as $sku => $stockDatum) {
            $stockItem = $this->stockItemFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $stockItem,
                $stockDatum,
                StockItemInterface::class
            );

            $stockItemConfiguration = $this->getStockConfiguration->forStockItem(
                $sku,
                $this->defaultStockProvider->getId()
            );
            foreach ($this->fields as $field => $type) {
                $setMethod = 'set' . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($field);
                $getMethod = 'get' . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($field);
                if ($stockItem->getData('use_config_' . $field)
                    || $stockItem->getData('use_config_' . $field) === null) {
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
