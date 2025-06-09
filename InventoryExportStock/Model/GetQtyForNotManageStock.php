<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryExportStock\Model;

/**
 * Class GetQtyForNotManageStock provides qtyForNotManageStock from di configuration
 */
class GetQtyForNotManageStock
{
    /**
     * @var float|null
     */
    private $qtyForNotManageStock;

    /**
     * GetQtyForNotManageStock constructor
     *
     * @param float|null $qtyForNotManageStock
     */
    public function __construct(
        ?float $qtyForNotManageStock
    ) {
        $this->qtyForNotManageStock = $qtyForNotManageStock;
    }

    /**
     * Provides qtyForNotManageStock from di configuration
     *
     * @return float|null
     */
    public function execute(): ?float
    {
        return $this->qtyForNotManageStock;
    }
}
