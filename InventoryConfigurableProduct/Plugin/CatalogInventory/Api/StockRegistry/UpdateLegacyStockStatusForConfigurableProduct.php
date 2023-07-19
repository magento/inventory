<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\Api\StockRegistry;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\ConfigurableProduct\Model\Inventory\ChangeParentStockStatus;

class UpdateLegacyStockStatusForConfigurableProduct
{
    /**
     * @var ChangeParentStockStatus
     */
    private $changeParentStockStatus;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param ChangeParentStockStatus $changeParentStockStatus
     * @param ProductFactory $productFactory
     */
    public function __construct(
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        ChangeParentStockStatus $changeParentStockStatus,
        ProductFactory $productFactory
    ) {
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->changeParentStockStatus = $changeParentStockStatus;
        $this->productFactory = $productFactory;
    }

    /**
     * @param StockRegistryInterface $subject
     * @param int $result
     * @param string $productSku
     * @param StockItemInterface $stockItem
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterUpdateStockItemBySku(
        StockRegistryInterface $subject,
        $result,
        string $productSku,
        StockItemInterface $stockItem
    ) {
        $productIds[] = $this->resolveProductId($productSku);
        if (!empty($productIds)) {
            $this->changeParentStockStatus->execute($productIds);
        }
        return $result;
    }

    /**
     * @param string $productSku
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function resolveProductId($productSku)
    {
        $product = $this->productFactory->create();
        $productId = $product->getIdBySku($productSku);
        if (!$productId) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __(
                    'The Product with the "%1" SKU doesn\'t exist.',
                    $productSku
                )
            );
        }
        return $productId;
    }
}
