<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Command\Input;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryReservationsApi\Model\ReservationBuilderInterface;
use Magento\InventoryReservationsApi\Model\ReservationInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 * Builds reservation model from given compensation input argument
 */
class GetReservationFromCompensationArgument
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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param OrderRepositoryInterface    $orderRepository
     * @param ReservationBuilderInterface $reservationBuilder
     * @param SerializerInterface         $serializer
     * @param SearchCriteriaBuilder|null  $searchCriteriaBuilder
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ReservationBuilderInterface $reservationBuilder,
        SerializerInterface $serializer,
        SearchCriteriaBuilder $searchCriteriaBuilder = null
    ) {
        $this->orderRepository = $orderRepository;
        $this->reservationBuilder = $reservationBuilder;
        $this->serializer = $serializer;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder
            ?: ObjectManager::getInstance()->get(SearchCriteriaBuilder::class);
    }

    /**
     * Parse CLI argument into order and order item information.
     *
     * @param string $argument
     * @return array
     * @throws InvalidArgumentException
     */
    private function parseArgument(string $argument): array
    {
        $pattern = '/(?P<increment_id>\d*):(?P<sku>.*):(?P<quantity>.*):(?P<stock_id>.*)/';
        if (preg_match($pattern, $argument, $match)) {
            return $match;
        }

        throw new InvalidArgumentException(sprintf('Given argument does not match pattern "%s"', $pattern));
    }

    /**
     * Builds reservation model from given compensation input argument.
     *
     * @param string $argument
     * @return ReservationInterface
     * @throws InvalidArgumentException
     * @throws ValidationException
     */
    public function execute(string $argument): ReservationInterface
    {
        $argumentParts = $this->parseArgument($argument);
        $results = $this->orderRepository->getList(
            $this->searchCriteriaBuilder->addFilter('increment_id', $argumentParts['increment_id'], 'eq')->create()
        );
        $order = current($results->getItems());

        return $this->reservationBuilder
            ->setSku((string)$argumentParts['sku'])
            ->setQuantity((float)$argumentParts['quantity'])
            ->setStockId((int)$argumentParts['stock_id'])
            ->setMetadata(
                $this->serializer->serialize(
                    [
                        'event_type' => 'manual_compensation',
                        'object_type' => 'order',
                        'object_id' => $order->getEntityId(),
                        'object_increment_id' => $order->getIncrementId(),
                    ]
                )
            )
            ->build();
    }
}
