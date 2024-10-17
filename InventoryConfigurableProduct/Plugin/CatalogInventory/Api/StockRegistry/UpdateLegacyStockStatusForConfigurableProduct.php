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
use Magento\ConfigurableProduct\Model\Inventory\ChangeParentStockStatus;

/***
 * Update stock status of configurable products when update children products stock status
 */
class UpdateLegacyStockStatusForConfigurableProduct
{
    /**
     * @var ChangeParentStockStatus
     */
    private $changeParentStockStatus;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @param ChangeParentStockStatus $changeParentStockStatus
     * @param ProductFactory $productFactory
     */
    public function __construct(
        ChangeParentStockStatus $changeParentStockStatus,
        ProductFactory $productFactory
    ) {
        $this->changeParentStockStatus = $changeParentStockStatus;
        $this->productFactory = $productFactory;
    }

    /**
     * Update product stock item by product SKU
     *
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
        $productIds = $this->resolveProductId($productSku);
        if ($productIds) {
            $this->changeParentStockStatus->execute([$productIds]);
        }
        return $result;
    }

    /**
     * Resolve and retrieve the product ID based on the SKU
     *
     * @param string $productSku
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function resolveProductId($productSku)
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
