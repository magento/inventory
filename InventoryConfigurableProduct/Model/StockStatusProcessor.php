<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Model;

use Magento\ConfigurableProduct\Model\Inventory\ChangeParentStockStatus;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\CompositeProductStockStatusProcessorInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;

/**
 * @inheritDoc
 */
class StockStatusProcessor implements CompositeProductStockStatusProcessorInterface
{
    /**
     * @var GetProductIdsBySkusInterface
     */
    private GetProductIdsBySkusInterface $getProductIdsBySkus;

    /**
     * @var ChangeParentStockStatus
     */
    private ChangeParentStockStatus $changeParentStockStatus;

    /**
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param ChangeParentStockStatus $changeParentStockStatus
     */
    public function __construct(
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        ChangeParentStockStatus $changeParentStockStatus
    ) {
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->changeParentStockStatus = $changeParentStockStatus;
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
