<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryGroupedProduct\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyRequestInterfaceFactory;

/**
 * Get grouped product stock status considering associated products service.
 */
class IsGroupedProductSalable
{
    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var AreProductsSalableForRequestedQtyInterface
     */
    private $areProductsSalableForRequestedQty;

    /**
     * @var IsProductSalableForRequestedQtyRequestInterfaceFactory
     */
    private $isProductSalableForRequestedQtyRequestFactory;

    /**
     * @var Grouped
     */
    private $groupedProductType;

    /**
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param AreProductsSalableForRequestedQtyInterface $areProductsSalableForRequestedQty
     * @param IsProductSalableForRequestedQtyRequestInterfaceFactory $isProductSalableForRequestedQtyRequestFactory
     * @param Grouped $groupedProductType
     */
    public function __construct(
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        AreProductsSalableForRequestedQtyInterface $areProductsSalableForRequestedQty,
        IsProductSalableForRequestedQtyRequestInterfaceFactory $isProductSalableForRequestedQtyRequestFactory,
        Grouped $groupedProductType
    ) {
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->areProductsSalableForRequestedQty = $areProductsSalableForRequestedQty;
        $this->isProductSalableForRequestedQtyRequestFactory = $isProductSalableForRequestedQtyRequestFactory;
        $this->groupedProductType = $groupedProductType;
    }

    /**
     * Get grouped product salable status considering associated products salable status.
     *
     * @param ProductInterface $groupedProduct
     * @param int $stockId
     * @return bool
     * @throws LocalizedException
     */
    public function execute(ProductInterface $groupedProduct, int $stockId): bool
    {
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($groupedProduct->getSku(), $stockId);
        if (!$stockItemConfiguration->getExtensionAttributes()->getIsInStock()) {
            return false;
        }

        $salable = false;
        $associatedProducts = $this->groupedProductType->getAssociatedProducts($groupedProduct);
        $skuRequests = [];
        foreach ($associatedProducts as $product) {
            $skuRequests[] = $this->isProductSalableForRequestedQtyRequestFactory->create(
                [
                    'sku' => (string)$product->getSku(),
                    'qty' => (float)$product->getQty(),
                ]
            );
        }
        $results = $this->areProductsSalableForRequestedQty->execute($skuRequests, $stockId);
        foreach ($results as $result) {
            if ($result->isSalable()) {
                $salable = true;
                break;
            }
        }

        return $salable;
    }
}
