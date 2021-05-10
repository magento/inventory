<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;

/**
 * Synchronization between legacy stock items and given source items after decrement quantity
 */
class DecrementQtyForLegacyStock
{
    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        DefaultSourceProviderInterface $defaultSourceProvider,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        ResourceConnection $resourceConnection
    ) {
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Decrement quantity for legacy stock after decrements quantity of msi stock
     *
     * @param array $decrementItems
     * @return void
     */
    public function execute(array $decrementItems): void
    {
        if (!count($decrementItems)) {
            return;
        }
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('cataloginventory_stock_item');
        foreach ($decrementItems as $decrementItem) {
            $sourceItem = $decrementItem['source_item'];
            $status = (int)$sourceItem->getStatus();
            if ($sourceItem->getSourceCode() !== $this->defaultSourceProvider->getCode()) {
                continue;
            }

            $sku = $sourceItem->getSku();
            try {
                $productId = (int)$this->getProductIdsBySkus->execute([$sku])[$sku];
            } catch (NoSuchEntityException $e) {
                // Skip synchronization for not existed product
                continue;
            }

            $where = [
                StockItemInterface::PRODUCT_ID . ' = ?' => $productId,
                'website_id = ?' => 0
            ];
            $connection->update(
                [$tableName],
                [
                    'qty' => new \Zend_Db_Expr('qty - ' . $decrementItem['qty_to_decrement']),
                    'is_in_stock' => $status
                ],
                $where
            );
        }
    }
}
