<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model;

use Magento\Bundle\Model\Inventory\ChangeParentStockStatus;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\CompositeProductStockStatusProcessorInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;

/**
 * @inheritDoc
 */
class StockStatusProcessor implements CompositeProductStockStatusProcessorInterface
{
    /**
     * @var ChangeParentStockStatus
     */
    private ChangeParentStockStatus $changeParentStockStatus;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private GetProductIdsBySkusInterface $getProductIdsBySkus;

    /**
     * @param ChangeParentStockStatus $changeParentStockStatus
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     */
    public function __construct(
        ChangeParentStockStatus $changeParentStockStatus,
        GetProductIdsBySkusInterface $getProductIdsBySkus
    ) {
        $this->changeParentStockStatus = $changeParentStockStatus;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $skus): void
    {
        $productIds = [];
        foreach ($skus as $sku) {
            try {
                $productIds[] = (int)$this->getProductIdsBySkus->execute([$sku])[$sku];
            } catch (NoSuchEntityException $e) {
                continue;
            }
        }
        $this->changeParentStockStatus->execute($productIds);
    }
}
