<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Inventory\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\Data\ReservationInterface;
use Magento\InventoryApi\Api\GetReservationsForProductInterface;
use Magento\InventoryApi\Api\ReservationRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class GetReservationsForProduct implements GetReservationsForProductInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ReservationRepositoryInterface
     */
    private $reservationRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ReservationRepositoryInterface $reservationRepository,
        LoggerInterface $logger
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->reservationRepository = $reservationRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute($sku, $stockId)
    {
        if (!is_numeric($stockId)) {
            throw new InputException(__('Input data is invalid'));
        }
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(ReservationInterface::SKU, $sku)
                ->addFilter(ReservationInterface::STOCK_ID, $stockId)
                ->create();
            $searchResult = $this->reservationRepository->getList($searchCriteria);
            return $searchResult->getItems();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new LocalizedException(__('Could not load Reservations for given Product and Stock'), $e);
        }
    }
}
