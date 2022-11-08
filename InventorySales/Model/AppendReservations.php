<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySales\Plugin\Sales\OrderManagement\AppendReservationsAfterOrderPlacementPlugin;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesEventExtensionFactory;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory;
use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote as QuoteEntity;
use Magento\Quote\Model\Quote\Item\ToOrderItem as ToOrderItemConverter;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Model\ResourceModel\Quote\Item;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 *  Append Reservation after Async Order is placed
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class AppendReservations
{
    /**
     * @var PlaceReservationsForSalesEventInterface
     */
    private $placeReservationsForSalesEvent;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var SalesChannelInterfaceFactory
     */
    private $salesChannelFactory;

    /**
     * @var SalesEventInterfaceFactory
     */
    private $salesEventFactory;

    /**
     * @var ItemToSellInterfaceFactory
     */
    private $itemsToSellFactory;

    /**
     * @var CheckItemsQuantity
     */
    private $checkItemsQuantity;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var SalesEventExtensionFactory;
     */
    private $salesEventExtensionFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var ToOrderItemConverter
     */
    private $quoteItemToOrderItem;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $quoteIdToMaskedQuoteId;

    /**
     * @param PlaceReservationsForSalesEventInterface $placeReservationsForSalesEvent
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param SalesChannelInterfaceFactory $salesChannelFactory
     * @param SalesEventInterfaceFactory $salesEventFactory
     * @param ItemToSellInterfaceFactory $itemsToSellFactory
     * @param CheckItemsQuantity $checkItemsQuantity
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param SalesEventExtensionFactory $salesEventExtensionFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param ToOrderItemConverter $quoteItemToOrderItem
     * @param ScopeConfigInterface $scopeConfig
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        PlaceReservationsForSalesEventInterface $placeReservationsForSalesEvent,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        WebsiteRepositoryInterface $websiteRepository,
        SalesChannelInterfaceFactory $salesChannelFactory,
        SalesEventInterfaceFactory $salesEventFactory,
        ItemToSellInterfaceFactory $itemsToSellFactory,
        CheckItemsQuantity $checkItemsQuantity,
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        SalesEventExtensionFactory $salesEventExtensionFactory,
        CartRepositoryInterface $quoteRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        ToOrderItemConverter $quoteItemToOrderItem,
        ScopeConfigInterface $scopeConfig,
        QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
    ) {
        $this->placeReservationsForSalesEvent = $placeReservationsForSalesEvent;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->websiteRepository = $websiteRepository;
        $this->salesChannelFactory = $salesChannelFactory;
        $this->salesEventFactory = $salesEventFactory;
        $this->itemsToSellFactory = $itemsToSellFactory;
        $this->checkItemsQuantity = $checkItemsQuantity;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->salesEventExtensionFactory = $salesEventExtensionFactory;
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteItemToOrderItem = $quoteItemToOrderItem;
        $this->scopeConfig = $scopeConfig;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
    }

    /**
     *
     * @param mixed $order
     */
    public function execute($order)
    {
        if ($this->scopeConfig->isSetFlag(
            AppendReservationsAfterOrderPlacementPlugin::CONFIG_PATH_USE_DEFERRED_STOCK_UPDATE
        )) {
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
            $websiteCode = $this->websiteRepository->getById($websiteId)->getCode();
            $stockId = (int)$this->stockByWebsiteIdResolver->execute((int)$websiteId)->getStockId();

            $this->checkItemsQuantity->execute($itemsBySku, $stockId);

            /** @var SalesEventExtensionInterface */
            $salesEventExtension = $this->salesEventExtensionFactory->create([
                'data' => ['objectIncrementId' => (string)$order->getIncrementId()]
            ]);

            /** @var SalesEventInterface $salesEvent */
            $salesEvent = $this->salesEventFactory->create([
                'type' => SalesEventInterface::EVENT_ORDER_PLACED,
                'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
                'objectId' => (string)$order->getEntityId()
            ]);
            $salesEvent->setExtensionAttributes($salesEventExtension);
            $salesChannel = $this->salesChannelFactory->create([
                'data' => [
                    'type' => SalesChannelInterface::TYPE_WEBSITE,
                    'code' => $websiteCode
                ]
            ]);

            $this->placeReservationsForSalesEvent->execute($itemsToSell, $salesChannel, $salesEvent);
        }
    }
    /**
     * Convert quote items to order items for quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    private function resolveItems(QuoteEntity $quote)
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
            $parentItem = isset($orderItems[$parentItemId]) ? $orderItems[$parentItemId] : null;
            $orderItems[$itemId] = $this->quoteItemToOrderItem->convert($quoteItem, ['parent_item' => $parentItem]);
        }
        return array_values($orderItems);
    }
}
