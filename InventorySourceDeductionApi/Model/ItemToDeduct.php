<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySourceDeductionApi\Model;

/**
 * @inheritdoc
 */
class ItemToDeduct implements ItemToDeductInterface
{
    /**
     * @var string
     */
    private $sku;

    /**
     * @var float
     */
    private $qty;

    /**
     * @param string $sku
     * @param float $qty
     */
    public function __construct(string $sku, float $qty)
    {
        $this->sku = $sku;
        $this->qty = $qty;
    }

    /**
     * @inheritdoc
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * @inheritdoc
     */
    public function getQty(): float
    {
        return $this->qty;
    }
}
