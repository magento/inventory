<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\Sales\OrderManagement;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySales\Model\AppendReservations;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventExtensionInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory;
use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;

/**
 * Add reservation during order placement
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AppendReservationsAfterOrderPlacementPlugin
{
    public const CONFIG_PATH_USE_DEFERRED_STOCK_UPDATE = 'cataloginventory/item_options/use_deferred_stock_update';

    /**
     * @var PlaceReservationsForSalesEventInterface
     */
    private $placeReservationsForSalesEvent;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var SalesEventInterfaceFactory
     */
    private $salesEventFactory;

    /**
     * @var ItemToSellInterfaceFactory
     */
    private $itemsToSellFactory;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var AppendReservations
     */
    private AppendReservations $appendReservations;

    /**
     * @param PlaceReservationsForSalesEventInterface $placeReservationsForSalesEvent
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param SalesEventInterfaceFactory $salesEventFactory
     * @param ItemToSellInterfaceFactory $itemsToSellFactory
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param ScopeConfigInterface $scopeConfig
     * @param AppendReservations $appendReservations
     */
    public function __construct(
        PlaceReservationsForSalesEventInterface $placeReservationsForSalesEvent,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        SalesEventInterfaceFactory $salesEventFactory,
        ItemToSellInterfaceFactory $itemsToSellFactory,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        ScopeConfigInterface $scopeConfig,
        AppendReservations $appendReservations
    ) {
        $this->placeReservationsForSalesEvent = $placeReservationsForSalesEvent;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->salesEventFactory = $salesEventFactory;
        $this->itemsToSellFactory = $itemsToSellFactory;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->scopeConfig = $scopeConfig;
        $this->appendReservations = $appendReservations;
    }

    /**
     * Add reservation before placing synchronous order or if "Use deferred Stock update" = Yes
     *
     * @param OrderManagementInterface $subject
     * @param callable $proceed
     * @param OrderInterface $order
     * @return OrderInterface
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundPlace(
        OrderManagementInterface $subject,
        callable $proceed,
        OrderInterface $order
    ): OrderInterface {
        if (!$order->getEntityId()
            || $this->scopeConfig->isSetFlag(self::CONFIG_PATH_USE_DEFERRED_STOCK_UPDATE)) {
            $itemsById = $itemsBySku = $itemsToSell = [];
            foreach ($order->getItems() as $item) {
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

                $itemsBySku[$sku] = (float)$itemsById[$productId];
                $itemsToSell[] = $this->itemsToSellFactory->create([
                    'sku' => $sku,
                    'qty' => -(float)$itemsById[$productId]
                ]);
            }

            $websiteId = (int)$order->getStore()->getWebsiteId();
            [$salesChannel, $salesEventExtension]
                = $this->appendReservations->reserve($websiteId, $itemsBySku, $order, $itemsToSell);
            $order = $this->createOrder($proceed, $order, $itemsToSell, $salesChannel, $salesEventExtension);
        } else {
            $order = $proceed($order);
        }
        return $order;
    }

    /**
     * Create new Order
     *
     * In case of error during order placement exception add compensation
     *
     * @param callable $proceed
     * @param OrderInterface $order
     * @param array $itemsToSell
     * @param SalesChannelInterface $salesChannel
     * @param SalesEventExtensionInterface $salesEventExtension
     * @return OrderInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createOrder($proceed, $order, $itemsToSell, $salesChannel, $salesEventExtension)
    {
        try {
            $order = $proceed($order);
        } catch (\Exception $e) {
            //add compensation
            foreach ($itemsToSell as $item) {
                $item->setQuantity(-(float)$item->getQuantity());
            }

            /** @var SalesEventInterface $salesEvent */
            $salesEvent = $this->salesEventFactory->create([
                'type' => SalesEventInterface::EVENT_ORDER_PLACE_FAILED,
                'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
                'objectId' => (string)$order->getEntityId()
            ]);
            $salesEvent->setExtensionAttributes($salesEventExtension);

            $this->placeReservationsForSalesEvent->execute($itemsToSell, $salesChannel, $salesEvent);

            throw $e;
        }
        return $order;
    }
}
