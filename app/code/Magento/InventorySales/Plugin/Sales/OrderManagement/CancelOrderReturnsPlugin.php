<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\Sales\OrderManagement;

use Magento\InventoryApi\Api\AppendReservationsInterface;
use Magento\InventoryApi\Api\ReservationBuilderInterface;
use Magento\InventoryCatalog\Model\GetSkusByProductIdsInterface;
use Magento\InventorySales\Model\StockByWebsiteIdResolver;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Class provides around Plugin on \Magento\Sales\Api\OrderManagementInterface::cancel
 */
class CancelOrderReturnsPlugin
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var ReservationBuilderInterface
     */
    private $reservationBuilder;
    /**
     * @var AppendReservationsInterface
     */
    private $appendReservations;
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;
    /**
     * @var StockByWebsiteIdResolver
     */
    private $stockByWebsiteIdResolver;
    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param ReservationBuilderInterface $reservationBuilder
     * @param AppendReservationsInterface $appendReservations
     * @param StoreRepositoryInterface $storeRepository
     * @param StockByWebsiteIdResolver $stockByWebsiteIdResolver
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ReservationBuilderInterface $reservationBuilder,
        AppendReservationsInterface $appendReservations,
        StoreRepositoryInterface $storeRepository,
        StockByWebsiteIdResolver $stockByWebsiteIdResolver,
        GetSkusByProductIdsInterface $getSkusByProductIds
    ) {
        $this->orderRepository = $orderRepository;
        $this->reservationBuilder = $reservationBuilder;
        $this->appendReservations = $appendReservations;
        $this->storeRepository = $storeRepository;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->getSkusByProductIds = $getSkusByProductIds;
    }

    /**
     * @param OrderManagementInterface $subject
     * @param callable $proceed
     * @param int $orderId
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCancel(OrderManagementInterface $subject, callable $proceed, $orderId)
    {
        return true;
        if ($proceed($orderId)) {
            $order = $this->orderRepository->get($orderId);
            $stockId = $this->getStockId($order);
            $orderItems = $order->getItems();

            $reservations = [];
            foreach ($orderItems as $orderItem) {
                $sku = current($this->getSkusByProductIds->execute([$orderItem->getProductId()]));
                $reservations[] = $this->reservationBuilder
                    ->setSku($sku)
                    ->setQuantity((float)$orderItem->getQtyCanceled()[$orderItem->getProductId()])
                    ->setStockId((int)$stockId)
                    ->setMetadata('For returns')
                    ->build();
            }
            $this->appendReservations->execute($reservations);

            return true;
        }

        return false;
    }

    /**
     * @param $order
     * @return int
     */
    private function getStockId(OrderInterface $order)
    {
        $store = $this->storeRepository->getById($order->getStoreId());
        $websiteId = $store->getWebsiteId();
        $stock = $this->stockByWebsiteIdResolver->get((int)$websiteId);

        return $stock->getStockId();
    }
}
