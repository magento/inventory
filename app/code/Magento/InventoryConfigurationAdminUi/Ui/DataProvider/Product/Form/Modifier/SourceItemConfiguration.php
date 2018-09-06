<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryConfigurationAdminUi\Model\ResourceModel\GetStockIdsBySourceCode;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;

/**
 * Product form modifier. Add to form source item configuration data.
 */
class SourceItemConfiguration extends AbstractModifier
{
    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var GetSourceConfigurationInterface
     */
    private $getSourceConfiguration;

    /**
     * @var GetStockIdsBySourceCode
     */
    private $getStockIdsBySourceCode;

    /**
     * @param LocatorInterface $locator
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param GetSourceConfigurationInterface $getSourceItemConfiguration
     * @param GetStockIdsBySourceCode $getStockIdsBySourceCode
     */
    public function __construct(
        LocatorInterface $locator,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        GetSourceConfigurationInterface $getSourceItemConfiguration,
        GetStockIdsBySourceCode $getStockIdsBySourceCode
    ) {
        $this->locator = $locator;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->getSourceConfiguration = $getSourceItemConfiguration;
        $this->getStockIdsBySourceCode = $getStockIdsBySourceCode;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $product = $this->locator->getProduct();
        if ($this->isSourceItemManagementAllowedForProductType->execute($product->getTypeId()) === false
            || null === $product->getId()
            || !isset($data[$product->getId()]['sources']['assigned_sources'])
        ) {
            return $data;
        }

        $assignedSources = $data[$product->getId()]['sources']['assigned_sources'];
        $data[$product->getId()]['sources']['assigned_sources'] = $this->getSourceItemsConfigurationData(
            $assignedSources,
            $product
        );

        return $data;
    }

    /**
     * @param array $assignedSources
     * @param ProductInterface $product
     * @return array
     */
    private function getSourceItemsConfigurationData(array $assignedSources, ProductInterface $product): array
    {
        $globalConfiguration = $this->getSourceConfiguration->forGlobal();
        foreach ($assignedSources as &$source) {
            $sourceConfiguration = $this->getSourceConfiguration->forSource(
                (string)$source[SourceInterface::SOURCE_CODE]
            );
            $sourceItemConfiguration = $this->getSourceConfiguration->forSourceItem(
                $product->getSku(),
                (string)$source[SourceInterface::SOURCE_CODE]
            );

            $source[SourceItemConfigurationInterface::NOTIFY_STOCK_QTY] = $this->getNotifyQtyConfigDataForSourceItem(
                $globalConfiguration,
                $sourceConfiguration,
                $sourceItemConfiguration
            );
            $source[SourceItemConfigurationInterface::NOTIFY_STOCK_QTY . '_source'] = $this->getNotifyQtyConfigData(
                $globalConfiguration,
                $sourceConfiguration
            );
            $source['notify_stock_qty_use_default'] = $sourceItemConfiguration->getNotifyStockQty() !== null
                ? "0"
                : "1";
            $source[SourceItemConfigurationInterface::BACKORDERS] = $this->getBackorderConfigDataForSourceItem(
                $globalConfiguration,
                $sourceConfiguration,
                $sourceItemConfiguration
            );

            $source[SourceItemConfigurationInterface::BACKORDERS . '_source'] = $this->getBackorderConfigData(
                $globalConfiguration,
                $sourceConfiguration
            );
            $source['backorders_use_default'] = $sourceItemConfiguration->getBackorders() !== null
                ? "0"
                : "1";
            $source['stock_ids'] = $this->getStockIds($source['source_code']);
        }
        unset($source);

        return $assignedSources;
    }

    /**
     * @param SourceItemConfigurationInterface $globalConfiguration
     * @param SourceItemConfigurationInterface $sourceConfiguration
     * @param SourceItemConfigurationInterface $sourceItemConfiguration
     * @return float
     */
    private function getNotifyQtyConfigDataForSourceItem(
        SourceItemConfigurationInterface $globalConfiguration,
        SourceItemConfigurationInterface $sourceConfiguration,
        SourceItemConfigurationInterface $sourceItemConfiguration
    ): float {
        $sourceNotifyQty = $sourceConfiguration->getNotifyStockQty() !== null
            ? $sourceConfiguration->getNotifyStockQty()
            : $globalConfiguration->getNotifyStockQty();

        return $sourceItemConfiguration->getNotifyStockQty() !== null
            ? $sourceItemConfiguration->getNotifyStockQty()
            : $sourceNotifyQty;
    }

    /**
     * @param SourceItemConfigurationInterface $globalConfiguration
     * @param SourceItemConfigurationInterface $sourceConfiguration
     * @param SourceItemConfigurationInterface $sourceItemConfiguration
     * @return int
     */
    private function getBackorderConfigDataForSourceItem(
        SourceItemConfigurationInterface $globalConfiguration,
        SourceItemConfigurationInterface $sourceConfiguration,
        SourceItemConfigurationInterface $sourceItemConfiguration
    ): int {
        $backorders = $sourceConfiguration->getBackorders() !== null
            ? $sourceConfiguration->getBackorders()
            : $globalConfiguration->getBackorders();

        return $sourceItemConfiguration->getBackorders() !== null
            ? $sourceItemConfiguration->getBackorders()
            : $backorders;
    }

    /**
     * @param SourceItemConfigurationInterface $globalConfiguration
     * @param SourceItemConfigurationInterface $sourceConfiguration
     * @return float
     */
    private function getNotifyQtyConfigData(
        SourceItemConfigurationInterface $globalConfiguration,
        SourceItemConfigurationInterface $sourceConfiguration
    ): float {
        return $sourceConfiguration->getNotifyStockQty() !== null
            ? $sourceConfiguration->getNotifyStockQty()
            : $globalConfiguration->getNotifyStockQty();
    }

    /**
     * @param SourceItemConfigurationInterface $globalConfiguration
     * @param SourceItemConfigurationInterface $sourceConfiguration
     * @return int
     */
    private function getBackorderConfigData(
        SourceItemConfigurationInterface $globalConfiguration,
        SourceItemConfigurationInterface $sourceConfiguration
    ): int {
        return $sourceConfiguration->getBackorders() !== null
            ? $sourceConfiguration->getBackorders()
            : $globalConfiguration->getBackorders();
    }

    /**
     * @param string $sourceCode
     * @return string
     */
    private function getStockIds(string $sourceCode): string
    {
        return implode(',', $this->getStockIdsBySourceCode->execute($sourceCode));
    }
}
