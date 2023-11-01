<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForSkuInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * Handles the retrieval of stock item data for products assigned to the default stock.
 */
class StockItemDataHandler
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resource;

    /**
     * @var DefaultStockProviderInterface
     */
    private DefaultStockProviderInterface $defaultStockProvider;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private GetProductIdsBySkusInterface $getProductIdsBySkus;

    /**
     * @var IsSingleSourceModeInterface
     */
    private IsSingleSourceModeInterface $isSingleSourceMode;

    /**
     * @var IsSourceItemManagementAllowedForSkuInterface
     */
    private IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku;

    /**
     * @param ResourceConnection $resource
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku
     */
    public function __construct(
        ResourceConnection $resource,
        DefaultStockProviderInterface $defaultStockProvider,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        IsSingleSourceModeInterface $isSingleSourceMode,
        IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku
    ) {
        $this->resource = $resource;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->isSourceItemManagementAllowedForSku = $isSourceItemManagementAllowedForSku;
    }

    /**
     * Retrieve stock item data for product assigned to the default stock.
     *
     * @param string $sku
     * @param int $stockId
     * @return array|null
     * @throws NoSuchEntityException
     */
    public function getStockItemDataFromStockItemTable(string $sku, int $stockId): ?array
    {
        if ($this->defaultStockProvider->getId() !== $stockId
            || $this->isSingleSourceMode->execute()
            || !$this->isSourceItemManagementAllowedForSku->execute($sku)
        ) {
            return null;
        }

        $productId = current($this->getProductIdsBySkus->execute([$sku]));
        $connection = $this->resource->getConnection();
        $select = $connection->select();

        $select->from(
            $this->resource->getTableName('cataloginventory_stock_item'),
            [
                GetStockItemDataInterface::QUANTITY => 'qty',
                GetStockItemDataInterface::IS_SALABLE => 'is_in_stock',
            ]
        )->where(
            'product_id = ?',
            $productId
        );

        return $connection->fetchRow($select) ?: null;
    }
}
