<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\InventoryApi\Model\IsProductAssignedToStockInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryConfiguration\Model\ResourceModel\IsManageStockActive;
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
     * @var IsManageStockActive
     */
    private $isManageStockActive;

    /**
     * @param GetLegacyStockItem $getLegacyStockItem
     * @param StockItemConfigurationFactory $stockItemConfigurationFactory
     * @param IsProductAssignedToStockInterface $isProductAssignedToStock
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku
     * @param IsManageStockActive $isManageStockActive
     */
    public function __construct(
        GetLegacyStockItem $getLegacyStockItem,
        StockItemConfigurationFactory $stockItemConfigurationFactory,
        IsProductAssignedToStockInterface $isProductAssignedToStock,
        DefaultStockProviderInterface $defaultStockProvider,
        IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku,
        IsManageStockActive $isManageStockActive
    ) {
        $this->getLegacyStockItem = $getLegacyStockItem;
        $this->stockItemConfigurationFactory = $stockItemConfigurationFactory;
        $this->isProductAssignedToStock = $isProductAssignedToStock;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->isSourceItemManagementAllowedForSku = $isSourceItemManagementAllowedForSku;
        $this->isManageStockActive = $isManageStockActive;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): StockItemConfigurationInterface
    {
        if ($this->defaultStockProvider->getId() !== $stockId
            && true === $this->isSourceItemManagementAllowedForSku->execute($sku)
            && false === $this->isProductAssignedToStock->execute($sku, $stockId)
            && true === (bool) $this->isManageStockActive->execute($sku)) {
            throw new SkuIsNotAssignedToStockException(
                __('The requested sku is not assigned to given stock.')
            );
        }

        return $this->stockItemConfigurationFactory->create(
            [
                'stockItem' => $this->getLegacyStockItem->execute($sku)
            ]
        );
    }
}
