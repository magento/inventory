<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Plugin\CatalogInventory\Model\ResourceModel\Stock\Item;

use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as ItemResourceModel;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\Model\AbstractModel;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;

/**
 * Save stock item configuration for given product and default stock after stock item was saved successfully.
 */
class SaveStockItemConfigurationPlugin
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
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

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
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        GetStockConfigurationInterface $getStockConfiguration,
        SaveStockConfigurationInterface $saveStockConfiguration,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->getStockConfiguration = $getStockConfiguration;
        $this->saveStockConfiguration = $saveStockConfiguration;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * @param ItemResourceModel $subject
     * @param ItemResourceModel $result
     * @param AbstractModel $stockItem
     * @return ItemResourceModel
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        ItemResourceModel $subject,
        ItemResourceModel $result,
        AbstractModel $stockItem
    ): ItemResourceModel {
        $productId = $stockItem->getProductId();
        $skus = $this->getSkusByProductIds->execute([$productId]);
        $productSku = $skus[$productId];

        $stockItemConfiguration = $this->getStockConfiguration->forStockItem(
            $productSku,
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

        $this->saveStockConfiguration->forStockItem(
            $productSku,
            $this->defaultStockProvider->getId(),
            $stockItemConfiguration
        );

        return $result;
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
