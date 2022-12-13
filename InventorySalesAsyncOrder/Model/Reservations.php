<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAsyncOrder\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySales\Model\AppendReservations;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Model\ResourceModel\Quote\Item;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Append Reservation after Order is placed
 */
class Reservations
{
    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var ItemToSellInterfaceFactory
     */
    private $itemsToSellFactory;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var ToOrderItem
     */
    private $quoteItemToOrderItem;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $quoteIdToMaskedQuoteId;

    /**
     * @var AppendReservations
     */
    private $appendReservations;

    /**
     * @param ItemToSellInterfaceFactory $itemsToSellFactory
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param ToOrderItem $quoteItemToOrderItem
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
     * @param AppendReservations $appendReservations
     */
    public function __construct(
        ItemToSellInterfaceFactory $itemsToSellFactory,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        CartRepositoryInterface $quoteRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        ToOrderItem $quoteItemToOrderItem,
        QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        AppendReservations $appendReservations
    ) {
        $this->itemsToSellFactory = $itemsToSellFactory;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteItemToOrderItem = $quoteItemToOrderItem;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
        $this->appendReservations = $appendReservations;
    }

    /**
     * Place inventory reservation for async order.
     *
     * @param mixed $order
     * @throws NoSuchEntityException
     */
    public function execute($order)
    {
        $itemsById = $itemsBySku = $itemsToSell = [];
        $cartId = $this->quoteIdToMaskedQuoteId->execute((int)$order->getQuoteId());
        if ($cartId) {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
        } else {
            $quote = $this->quoteRepository->get($order->getQuoteId());
        }
        foreach ($this->resolveItems($quote) as $item) {
            if (!isset($itemsById[$item->getProductId()])) {
                $itemsById[$item->getProductId()] = 0;
            }
            $itemsById[$item->getProductId()] += $item->getQtyOrdered();
        }
        $productSkus = $this->getSkusByProductIds->execute(array_keys($itemsById));
        $productTypes = $this->getProductTypesBySkus->execute($productSkus);

        foreach ($productSkus as $productId => $sku) {
            if (false === $this->isSourceItemManagementAllowedForProductType->execute($productTypes[$sku])) {
                continue;
            }
            if ($order instanceof OrderInterface) {
                $qty = (float)$itemsById[$productId];
            } else {
                $qty = -(float)$itemsById[$productId];
            }

            $itemsBySku[$sku] = (float)$itemsById[$productId];
            $itemsToSell[] = $this->itemsToSellFactory->create([
                'sku' => $sku,
                'qty' => $qty
            ]);
        }

        $websiteId = (int)$quote->getStore()->getWebsiteId();
        $this->appendReservations->reserve($websiteId, $itemsBySku, $order, $itemsToSell);
    }

    /**
     * Convert quote items to order items for quote
     *
     * @param Quote $quote
     * @return array
     */
    private function resolveItems(Quote $quote)
    {
        $orderItems = [];
        foreach ($quote->getAllItems() as $quoteItem) {
            $itemId = $quoteItem->getId();

            if (!empty($orderItems[$itemId])) {
                continue;
            }

            $parentItemId = $quoteItem->getParentItemId();
            /** @var Item $parentItem */
            if ($parentItemId && !isset($orderItems[$parentItemId])) {
                $orderItems[$parentItemId] = $this->quoteItemToOrderItem->convert(
                    $quoteItem->getParentItem(),
                    ['parent_item' => null]
                );
            }
            $parentItem = $orderItems[$parentItemId] ?? null;
            $orderItems[$itemId] = $this->quoteItemToOrderItem->convert($quoteItem, ['parent_item' => $parentItem]);
        }
        return array_values($orderItems);
    }
}
