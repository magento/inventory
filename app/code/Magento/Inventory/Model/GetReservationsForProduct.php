<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Inventory\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Inventory\Model\ResourceModel\Reservation\GetReservationsForProduct as GetReservationsForProductResourceModel;
use Magento\InventoryApi\Api\GetReservationsForProductInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class GetReservationsForProduct implements GetReservationsForProductInterface
{
    /**
     * @var GetReservationsForProductResourceModel
     */
    private $getReservationsForProduct;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        GetReservationsForProductResourceModel $getReservationsForProduct,
        LoggerInterface $logger
    ) {
        $this->getReservationsForProduct = $getReservationsForProduct;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): array
    {
        try {
            $reservations = $this->getReservationsForProduct->execute($sku, $stockId);
            return $reservations;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new LocalizedException(__('Could not load Reservations for given Product and Stock'), $e);
        }
    }
}
