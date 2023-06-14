<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservations\Model\ResourceModel;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;

/**
 * @inheritdoc
 */
class GetReservationsQuantityCache implements GetReservationsQuantityInterface, ResetAfterRequestInterface
{
    /**
     * @var GetReservationsQuantity
     */
    private $getReservationsQuantity;

    /**
     * @var array
     */
    private $reservationsQuantity = [[]];

    /**
     * @param GetReservationsQuantity $getReservationsQuantity
     */
    public function __construct(
        GetReservationsQuantity $getReservationsQuantity
    ) {
        $this->getReservationsQuantity = $getReservationsQuantity;
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->reservationsQuantity = [[]];
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): float
    {
        if (!isset($this->reservationsQuantity[$sku][$stockId])) {
            $this->reservationsQuantity[$sku][$stockId] = $this->getReservationsQuantity->execute($sku, $stockId);
        }

        return $this->reservationsQuantity[$sku][$stockId];
    }
}
