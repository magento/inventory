<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\SalableQuantityInconsistency;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryReservationCli\Model\ResourceModel\GetOrderItemsDataForOrdersInNotFinalState;
use Magento\InventoryReservationCli\Model\StoreWebsiteResolver;
use Magento\InventoryReservationsApi\Model\ReservationBuilderInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;

/**
 * Add expected reservations by current incomplete orders
 */
class AddExpectedReservations
{
    /**
     * @var ReservationBuilderInterface
     */
    private $reservationBuilder;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var GetOrderItemsDataForOrdersInNotFinalState
     */
    private $getOrderItemsDataForOrderInNotFinalState;
    /**
     * @var StoreWebsiteResolver
     */
    private $storeWebsiteResolver;

    /**
     * @param ReservationBuilderInterface $reservationBuilder
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param SerializerInterface $serializer
     * @param GetOrderItemsDataForOrdersInNotFinalState $getOrderItemsDataForOrderInNotFinalState
     * @param StoreWebsiteResolver $storeWebsiteResolver
     */
    public function __construct(
        ReservationBuilderInterface $reservationBuilder,
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        SerializerInterface $serializer,
        GetOrderItemsDataForOrdersInNotFinalState $getOrderItemsDataForOrderInNotFinalState,
        StoreWebsiteResolver $storeWebsiteResolver
    ) {
        $this->reservationBuilder = $reservationBuilder;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->serializer = $serializer;
        $this->getOrderItemsDataForOrderInNotFinalState = $getOrderItemsDataForOrderInNotFinalState;
        $this->storeWebsiteResolver = $storeWebsiteResolver;
    }

    /**
     * Add expected reservations by current incomplete orders.
     *
     * @param Collector $collector
     * @param int $bunchSize
     * @param int $page
     * @throws ValidationException
     */
    public function execute(Collector $collector, int $bunchSize = 50, int $page = 1): void
    {
        foreach ($this->getOrderItemsDataForOrderInNotFinalState->execute($bunchSize, $page) as $data) {
            $websiteId = $this->storeWebsiteResolver->execute((int) $data['store_id']);
            $stockId = (int)$this->stockByWebsiteIdResolver->execute((int)$websiteId)->getStockId();

            $reservation = $this->reservationBuilder
                ->setSku($data['sku'])
                ->setQuantity((float)$data['qty_ordered'])
                ->setStockId($stockId)
                ->setMetadata($this->serializer->serialize(['object_id' => (int)$data['entity_id']]))
                ->build();

            $collector->addReservation($reservation);
            $collector->addOrderData($data + ['website_id' => $websiteId]);
        }
    }
}
