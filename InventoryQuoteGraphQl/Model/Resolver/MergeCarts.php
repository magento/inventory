<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryQuoteGraphQl\Model\Resolver;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Cart\CustomerCartResolver;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;

/**
 * Merge Carts Resolver
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class MergeCarts implements ResolverInterface
{
    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CustomerCartResolver
     */
    private $customerCartResolver;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $quoteIdToMaskedQuoteId;

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
     * @param GetCartForUser $getCartForUser
     * @param CartRepositoryInterface $cartRepository
     * @param CustomerCartResolver $customerCartResolver
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
     * @param CartItemRepositoryInterface $cartItemRepository
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     */
    public function __construct(
        GetCartForUser $getCartForUser,
        CartRepositoryInterface $cartRepository,
        CustomerCartResolver $customerCartResolver,
        QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        CartItemRepositoryInterface $cartItemRepository,
        GetProductSalableQtyInterface $getProductSalableQty,
        GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
    ) {
        $this->getCartForUser = $getCartForUser;
        $this->cartRepository = $cartRepository;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
        $this->customerCartResolver = $customerCartResolver;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
        $this->cartItemRepository = $cartItemRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (empty($args['source_cart_id'])) {
            throw new GraphQlInputException(__(
                'Required parameter "source_cart_id" is missing'
            ));
        }

        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__(
                'The current customer isn\'t authorized.'
            ));
        }
        $currentUserId = $context->getUserId();

        if (!isset($args['destination_cart_id'])) {
            try {
                $cart = $this->customerCartResolver->resolve($currentUserId);
            } catch (CouldNotSaveException $exception) {
                throw new GraphQlNoSuchEntityException(
                    __('Could not create empty cart for customer'),
                    $exception
                );
            }
            $customerMaskedCartId = $this->quoteIdToMaskedQuoteId->execute(
                (int) $cart->getId()
            );
        } else {
            if (empty($args['destination_cart_id'])) {
                throw new GraphQlInputException(__(
                    'The parameter "destination_cart_id" cannot be empty'
                ));
            }
        }

        $guestMaskedCartId = $args['source_cart_id'];
        $customerMaskedCartId = $customerMaskedCartId ?? $args['destination_cart_id'];

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        // passing customerId as null enforces source cart should always be a guestcart
        $guestCart = $this->getCartForUser->execute(
            $guestMaskedCartId,
            null,
            $storeId
        );
        $customerCart = $this->getCartForUser->execute(
            $customerMaskedCartId,
            $currentUserId,
            $storeId
        );
        if ($this->validateFinalCartQuantities($customerCart, $guestCart)) {
            $guestCart = $this->getCartForUser->execute(
                $guestMaskedCartId,
                null,
                $storeId
            );
        }
        $customerCart->merge($guestCart);
        $guestCart->setIsActive(false);
        $this->cartRepository->save($customerCart);
        $this->cartRepository->save($guestCart);
        return [
            'model' => $customerCart,
        ];
    }

    /**
     * Validate combined cart quantities to make sure they are within available stock
     *
     * @param CartInterface $customerCart
     * @param CartInterface $guestCart
     * @return bool
     */
    private function validateFinalCartQuantities(CartInterface $customerCart, CartInterface $guestCart)
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
