<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\SalableQuantityInconsistency;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\InventoryReservationCli\Model\ResourceModel\GetOrderIncrementId;
use Magento\InventoryReservationCli\Model\ResourceModel\GetReservationsList;
use Magento\InventoryReservationsApi\Model\ReservationBuilderInterface;
use Magento\InventoryReservationsApi\Model\ReservationInterface;

/**
 * Load existing reservations
 */
class LoadExistingReservations
{
    /**
     * @var GetReservationsList
     */
    private $getReservationsList;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ReservationBuilderInterface
     */
    private $reservationBuilder;

    /**
     * @var GetOrderIncrementId
     */
    private $getOrderIncrementId;

    /**
     * @var string[]
     */
    private $orderIncrementIds = [];

    /**
     * @param GetReservationsList $getReservationsList
     * @param SerializerInterface $serializer
     * @param ReservationBuilderInterface $reservationBuilder
     * @param GetOrderIncrementId $getOrderIncrementId
     */
    public function __construct(
        GetReservationsList $getReservationsList,
        SerializerInterface $serializer,
        ReservationBuilderInterface $reservationBuilder,
        GetOrderIncrementId $getOrderIncrementId
    ) {
        $this->getReservationsList = $getReservationsList;
        $this->serializer = $serializer;
        $this->reservationBuilder = $reservationBuilder;
        $this->getOrderIncrementId = $getOrderIncrementId;
    }

    /**
     * Load existing reservations
     *
     * @return array<string, ReservationInterface[]>
     */
    public function execute(): array
    {
        $result = [];
        $reservationList = $this->getReservationsList->execute();
        foreach ($reservationList as $reservation) {
            /** @var array $metadata */
            $metadata = $this->serializer->unserialize($reservation['metadata']);
            if ($metadata['object_type'] !== 'order') {
                continue;
            }

            if (!isset($metadata['object_increment_id'])) {
                $metadata['object_increment_id'] = $this->getOrderIncrementId(
                    (int)$metadata['object_id']
                );
            }
            $reservationModel = $this->reservationBuilder
                ->setMetadata($this->serializer->serialize($metadata))
                ->setStockId((int)$reservation['stock_id'])
                ->setSku($reservation['sku'])
                ->setQuantity((float)$reservation['quantity'])
                ->build();

            $key = $metadata['object_increment_id'] . '-' . $reservation['stock_id'];
            $result[$key][] = $reservationModel;
        }

        return $result;
    }

    /**
     * Load order increment id by order id
     *
     * @param int $orderId
     * @return string
     */
    private function getOrderIncrementId(int $orderId): string
    {
        if (!isset($this->orderIncrementIds[$orderId])) {
            $this->orderIncrementIds[$orderId] = $this->getOrderIncrementId->execute($orderId);
        }

        return $this->orderIncrementIds[$orderId];
    }
}
