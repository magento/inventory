<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryQuoteGraphQl\Model\Cart\MergeCarts;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface as IsProductSalableForRequestedQty;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\QuoteGraphQl\Model\Cart\MergeCarts\CartQuantityValidatorInterface;
use Magento\Checkout\Model\Config;
use Psr\Log\LoggerInterface;

class CartQuantityValidator implements CartQuantityValidatorInterface
{
    /**
     * Array to hold cumulative quantities for each SKU
     *
     * @var array
     */
    private array $cumulativeQty = [];

    /**
     * @param CartItemRepositoryInterface $cartItemRepository
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     * @param Config $config
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     * @param IsProductSalableForRequestedQty $isProductSalableForRequestedQty
     */
    public function __construct(
        private readonly CartItemRepositoryInterface     $cartItemRepository,
        private readonly GetStockIdForCurrentWebsite     $getStockIdForCurrentWebsite,
        private readonly Config                          $config,
        private readonly ProductRepositoryInterface      $productRepository,
        private readonly LoggerInterface                 $logger,
        private readonly IsProductSalableForRequestedQty $isProductSalableForRequestedQty,
    ) {
    }

    /**
     * Validate combined cart quantities to ensure they are within available stock
     *
     * @param CartInterface $customerCart
     * @param CartInterface $guestCart
     * @return bool
     * @throws NoSuchEntityException
     */
    public function validateFinalCartQuantities(CartInterface $customerCart, CartInterface $guestCart): bool
    {
        $modified = false;
        $this->cumulativeQty = [];
        $stockId = $this->getStockIdForCurrentWebsite->execute();
        $mergePreference = $this->config->getCartMergePreference();

        foreach ($guestCart->getAllVisibleItems() as $guestItem) {
            foreach ($customerCart->getAllItems() as $customerItem) {
                if (!$customerItem->compare($guestItem)) {
                    continue;
                }

                if ($mergePreference === Config::CART_PREFERENCE_CUSTOMER) {
                    $this->safeDeleteCartItem((int)$guestCart->getId(), (int)$guestItem->getItemId());
                    $modified = true;
                    break;
                }

                $sku = $this->resolveSku($customerItem);

                $isQtyValid = $customerItem->getChildren()
                    ? $this->validateCompositeProductQty($stockId, $guestItem, $customerItem)
                    : $this->validateProductQty(
                        $stockId,
                        $sku,
                        $guestItem->getQty(),
                        $customerItem->getQty(),
                    );

                if ($mergePreference === Config::CART_PREFERENCE_GUEST) {
                    $this->safeDeleteCartItem((int)$customerCart->getId(), (int)$customerItem->getItemId());
                    $modified = true;
                }

                if (!$isQtyValid) {
                    $this->safeDeleteCartItem((int)$guestCart->getId(), (int)$guestItem->getItemId());
                    $modified = true;
                }

                break;
            }
        }

        $this->cumulativeQty = [];

        return $modified;
    }

    /**
     * Validate product quantity against available stock
     *
     * @param int $stockId
     * @param string $sku
     * @param float $guestItemQty
     * @param float $customerItemQty
     * @return bool
     */
    private function validateProductQty(int $stockId, string $sku, float $guestItemQty, float $customerItemQty): bool
    {
        $this->cumulativeQty[$sku] ??= 0;
        $this->cumulativeQty[$sku] += $this->getCurrentCartItemQty($guestItemQty, $customerItemQty);
        $salableResult = $this->isProductSalableForRequestedQty->execute($sku, $stockId, $this->cumulativeQty[$sku]);

        return $salableResult->isSalable();
    }

    /**
     * Validate composite product quantities against available stock
     *
     * @param int $stockId
     * @param Item $guestItem
     * @param Item $customerItem
     * @return bool
     * @throws NoSuchEntityException
     */
    private function validateCompositeProductQty(int $stockId, Item $guestItem, Item $customerItem): bool
    {
        $guestChildren = $guestItem->getChildren();
        $customerChildren = $customerItem->getChildren();

        /** @var CartItemInterface $customerChild */
        foreach ($customerChildren as $customerChild) {
            $sku = $this->resolveSku($customerChild);
            $guestChild = $this->retrieveChildItems($guestChildren, $sku);

            $guestQty = $guestChild ? $guestItem->getQty() * $guestChild->getQty() : 0;
            $customerQty = $customerItem->getQty() * $customerChild->getQty();
            if (!$this->validateProductQty($stockId, $sku, $guestQty, $customerQty)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve SKU for a cart item, handling products with options
     *
     * @param CartItemInterface $item
     * @return string
     * @throws NoSuchEntityException
     */
    private function resolveSku(CartItemInterface $item): string
    {
        /** @var \Magento\Quote\Model\Quote\Item $item */
        $product = $item->getProduct();
        if ($product->getOptions()) {
            return $this->productRepository->getById($product->getId())->getSku();
        }

        return $product->getSku();
    }

    /**
     * Find a child item by SKU in the list of children
     *
     * @param CartItemInterface[]|array $children
     * @param string $sku
     * @return CartItemInterface|null
     */
    private function retrieveChildItems(array $children, string $sku): ?CartItemInterface
    {
        foreach ($children as $child) {
            if ($child->getProduct()->getSku() === $sku) {
                return $child;
            }
        }

        return null;
    }

    /**
     * Get the current cart item quantity based on the merge preference
     *
     * @param float $guestCartItemQty
     * @param float $customerCartItemQty
     * @return float
     */
    private function getCurrentCartItemQty(float $guestCartItemQty, float $customerCartItemQty): float
    {
        return match ($this->config->getCartMergePreference()) {
            Config::CART_PREFERENCE_CUSTOMER => $customerCartItemQty,
            Config::CART_PREFERENCE_GUEST => $guestCartItemQty,
            default => $guestCartItemQty + $customerCartItemQty
        };
    }

    /**
     * Safely delete a cart item by ID, logging any exceptions
     *
     * @param int $cartId
     * @param int $itemId
     * @return void
     */
    private function safeDeleteCartItem(int $cartId, int $itemId): void
    {
        try {
            $this->cartItemRepository->deleteById($cartId, $itemId);
        } catch (NoSuchEntityException | CouldNotSaveException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
