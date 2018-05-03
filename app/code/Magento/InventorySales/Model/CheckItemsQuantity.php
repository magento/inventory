<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventoryCatalog\Model\GetProductTypesBySkusInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\InventoryConfiguration\Model\IsSourceItemsAllowedForProductTypeInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterface;
use Magento\InventoryCatalog\Api\DefaultStockProviderInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

class CheckItemsQuantity
{
    /**
     * @var IsSourceItemsAllowedForProductTypeInterface
     */
    private $isSourceItemsAllowedForProductType;

    /**
     * @var IsProductSalableForRequestedQtyInterface
     */
    private $isProductSalableForRequestedQty;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param IsSourceItemsAllowedForProductTypeInterface $isSourceItemsAllowedForProductType
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        IsSourceItemsAllowedForProductTypeInterface $isSourceItemsAllowedForProductType,
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty,
        DefaultStockProviderInterface $defaultStockProvider,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        StockResolverInterface $stockResolver
    ) {
        $this->isSourceItemsAllowedForProductType = $isSourceItemsAllowedForProductType;
        $this->isProductSalableForRequestedQty = $isProductSalableForRequestedQty;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->stockResolver = $stockResolver;
    }

    /**
     * Check whether all items salable
     *
     * @param array $items [['sku' => 'qty'], ...]
     * @param SalesChannelInterface $salesChannel
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute(array $items, SalesChannelInterface $salesChannel) : void
    {
        $productTypes = $this->getProductTypesBySkus->execute(array_keys($items));
        foreach ($items as $sku => $qty) {
            if (false === $this->isSourceItemsAllowedForProductType->execute($productTypes[$sku])) {
                $stockId = (int)$this->stockResolver
                    ->get($salesChannel->getType(), $salesChannel->getCode())
                    ->getStockId();
                $defaultStockId = $this->defaultStockProvider->getId();
                if ($defaultStockId !== $stockId) {
                    throw new LocalizedException(
                        __('Product type is not supported on Default Stock.')
                    );
                }
                continue;
            }
            /** @var ProductSalableResultInterface $isSalable */
            $isSalable = $this->isProductSalableForRequestedQty->execute($sku, $salesChannel, $qty);
            if (false === $isSalable->isSalable()) {
                $errors = $isSalable->getErrors();
                /** @var ProductSalabilityErrorInterface $errorMessage */
                $errorMessage = array_pop($errors);
                throw new LocalizedException(__($errorMessage->getMessage()));
            }
        }
    }
}
