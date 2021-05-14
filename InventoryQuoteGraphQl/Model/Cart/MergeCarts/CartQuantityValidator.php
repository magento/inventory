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
        /** @var CartItemInterface $guestCartItem */
        foreach ($guestCart->getAllVisibleItems() as $guestCartItem) {
            foreach ($customerCart->getAllItems() as $customerCartItem) {
                if ($customerCartItem->compare($guestCartItem)) {
                    $product = $customerCartItem->getProduct();
                    $productSalableQty = $this->getProductSalableQty->execute($product->getSku(), $stockId);
                    if ($productSalableQty < $guestCartItem->getQty() + $customerCartItem->getQty()) {
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
        return $modified;
    }
}
