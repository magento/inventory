<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\Queue;

/**
 * Data object for reservations queue request.
 */
class ReservationData
{
    /**
     * @var string[]
     */
    private $skus;

    /**
     * @var int
     */
    private $stockId;

    /**
     * @param string[] $skus
     * @param int $stock
     */
    public function __construct(array $skus, int $stock)
    {
        $this->skus = $skus;
        $this->stockId = $stock;
    }

    /**
     * Retrieve products SKUs to process.
     *
     * @return string[]
     */
    public function getSkus(): array
    {
        return $this->skus;
    }

    /**
     * Retrieve stock id.
     *
     * @return int
     */
    public function getStock(): int
    {
        return $this->stockId;
    }
}
