<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryQuoteGraphQl\Model\Cart\MergeCarts;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\QuoteGraphQl\Model\Cart\MergeCarts\CartQuantityValidatorInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;

class CartQuantityValidator implements CartQuantityValidatorInterface
{
    /**
     * @var CartItemRepositoryInterface
     */
    private $cartItemRepository;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @var GetStockIdForCurrentWebsite
     */
    private $getStockIdForCurrentWebsite;

    /**
     * @var array
     */
    private $cumulativeQty = [];

    /**
     * @param CartItemRepositoryInterface $cartItemRepository
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     */
    public function __construct(
        CartItemRepositoryInterface $cartItemRepository,
        GetProductSalableQtyInterface $getProductSalableQty,
        GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
    ) {
        $this->cartItemRepository = $cartItemRepository;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
    }

    /**
     * Validate combined cart quantities to make sure they are within available stock
     *
     * @param CartInterface $customerCart
     * @param CartInterface $guestCart
     * @return bool
     */
    public function validateFinalCartQuantities(CartInterface $customerCart, CartInterface $guestCart): bool
    {
        $modified = false;
        $stockId = $this->getStockIdForCurrentWebsite->execute();
        $this->cumulativeQty = [];

        /** @var CartItemInterface $guestCartItem */
        foreach ($guestCart->getAllVisibleItems() as $guestCartItem) {
            foreach ($customerCart->getAllItems() as $customerCartItem) {
                if ($customerCartItem->compare($guestCartItem)) {
                    $enoughQty = $customerCartItem->getChildren()
                        ? $this->validateCompositeProductQty($stockId, $guestCartItem, $customerCartItem)
                        : $this->validateProductQty(
                            $stockId,
                            $customerCartItem->getProduct()->getSku(),
                            $guestCartItem->getQty(),
                            $customerCartItem->getQty()
                        );

                    if (!$enoughQty) {
                        try {
                            $this->cartItemRepository->deleteById($guestCart->getId(), $guestCartItem->getItemId());
                            $modified = true;
                        } catch (NoSuchEntityException $e) {
                            continue;
                        } catch (CouldNotSaveException $e) {
                            continue;
                        }
                    }
                }
            }
        }
        $this->cumulativeQty = [];

        return $modified;
    }

    /**
     * Validate product stock availability
     *
     * @param int $stockId
     * @param string $sku
     * @param float $guestItemQty
     * @param float $customerItemQty
     * @return bool
     */
    private function validateProductQty(int $stockId, string $sku, float $guestItemQty, float $customerItemQty): bool
    {
        $salableQty = $this->getProductSalableQty->execute($sku, $stockId);
        $this->cumulativeQty[$sku] ??= 0;
        $this->cumulativeQty[$sku] += $guestItemQty + $customerItemQty;

        return $salableQty >= $this->cumulativeQty[$sku];
    }

    /**
     * Validate composite product stock availability
     *
     * @param int $stockId
     * @param Item $guestCartItem
     * @param Item $customerCartItem
     * @return bool
     */
    private function validateCompositeProductQty(int $stockId, Item $guestCartItem, Item $customerCartItem): bool
    {
        $guestChildItems = $this->retrieveChildItems($guestCartItem);
        foreach ($customerCartItem->getChildren() as $customerChildItem) {
            $sku = $customerChildItem->getProduct()->getSku();
            $customerItemQty = $customerCartItem->getQty() * $customerChildItem->getQty();
            $guestItemQty = $guestCartItem->getQty() * $guestChildItems[$sku]->getQty();
            if (!$this->validateProductQty($stockId, $sku, $guestItemQty, $customerItemQty)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retrieve child quote items mapped by sku
     *
     * @param Item $quoteItem
     * @return array
     */
    private function retrieveChildItems(Item $quoteItem): array
    {
        $childItems = [];
        foreach ($quoteItem->getChildren() as $childItem) {
            $childItems[$childItem->getProduct()->getSku()] = $childItem;
        }

        return $childItems;
    }
}
