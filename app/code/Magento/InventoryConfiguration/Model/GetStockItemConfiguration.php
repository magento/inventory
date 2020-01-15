<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\InventoryApi\Model\IsProductAssignedToStockInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForSkuInterface;

/**
 * @inheritdoc
 */
class GetStockItemConfiguration implements GetStockItemConfigurationInterface
{
    /**
     * @var GetLegacyStockItem
     */
    private $getLegacyStockItem;

    /**
     * @var StockItemConfigurationFactory
     */
    private $stockItemConfigurationFactory;

    /**
     * @var IsProductAssignedToStockInterface
     */
    private $isProductAssignedToStock;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var IsSourceItemManagementAllowedForSkuInterface
     */
    private $isSourceItemManagementAllowedForSku;

    /**
     * @var StockItemConfigurationInterface[]
     */
    private $processedStockData;

    /**
     * @param GetLegacyStockItem $getLegacyStockItem
     * @param StockItemConfigurationFactory $stockItemConfigurationFactory
     * @param IsProductAssignedToStockInterface $isProductAssignedToStock
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku
     */
    public function __construct(
        GetLegacyStockItem $getLegacyStockItem,
        StockItemConfigurationFactory $stockItemConfigurationFactory,
        IsProductAssignedToStockInterface $isProductAssignedToStock,
        DefaultStockProviderInterface $defaultStockProvider,
        IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku
    ) {
        $this->getLegacyStockItem = $getLegacyStockItem;
        $this->stockItemConfigurationFactory = $stockItemConfigurationFactory;
        $this->isProductAssignedToStock = $isProductAssignedToStock;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->isSourceItemManagementAllowedForSku = $isSourceItemManagementAllowedForSku;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): StockItemConfigurationInterface
    {
        if ($this->processedStockData !== null && isset($this->processedStockData[$stockId][$sku])) {
            return $this->processedStockData[$stockId][$sku];
        }

        if ($this->defaultStockProvider->getId() !== $stockId
            && true === $this->isSourceItemManagementAllowedForSku->execute($sku)
            && false === $this->isProductAssignedToStock->execute($sku, $stockId)) {
            throw new SkuIsNotAssignedToStockException(
                __('The requested sku is not assigned to given stock.')
            );
        }

        $this->processedStockData[$stockId][$sku] = $this->stockItemConfigurationFactory->create(
            [
                'stockItem' => $this->getLegacyStockItem->execute($sku)
            ]
        );

        return $this->processedStockData[$stockId][$sku];
    }
}
