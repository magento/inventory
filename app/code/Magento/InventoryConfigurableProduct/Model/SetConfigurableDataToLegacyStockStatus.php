<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Model;

use Magento\CatalogInventory\Model\Indexer\Stock;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalog\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalog\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalog\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

class SetConfigurableDataToLegacyStockStatus
{
    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var SetConfigurableDataToLegacyStockStatus
     */
    private $setDataToLegacyStockStatus;

    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var Configurable
     */
    private $configurable;

    /**
     * @var Stock
     */
    private $stock;

    /**
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param SetDataToLegacyStockStatus $setDataToLegacyStockStatus
     * @param IsProductSalableInterface $isProductSalable
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param Configurable $configurable
     * @param Stock $stock
     */
    public function __construct(
        DefaultSourceProviderInterface $defaultSourceProvider,
        SetDataToLegacyStockStatus $setDataToLegacyStockStatus,
        IsProductSalableInterface $isProductSalable,
        DefaultStockProviderInterface $defaultStockProvider,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        Configurable $configurable,
        Stock $stock
    ) {
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->setDataToLegacyStockStatus = $setDataToLegacyStockStatus;
        $this->isProductSalable = $isProductSalable;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->configurable = $configurable;
        $this->stock = $stock;
    }

    /**
     * @param SourceItemInterface[] $sourceItems
     * @return void
     */
    public function execute(array $sourceItems): void
    {
        foreach ($sourceItems as $sourceItem) {
            if ($sourceItem->getSourceCode() !== $this->defaultSourceProvider->getCode()) {
                continue;
            }
            $sku = $sourceItem->getSku();
            $productIds = $this->getProductIdsBySkus->execute([$sku]);

            $parentProductIds = $this->configurable->getParentIdsByChild($productIds);

            if (!empty($parentProductIds)) {
                $parentProductIdsInt = array_map(
                    function ($id) {
                        return (int)$id;
                    },
                    $parentProductIds
                );

                $this->stock->execute($parentProductIdsInt);
            }
        }
    }
}

