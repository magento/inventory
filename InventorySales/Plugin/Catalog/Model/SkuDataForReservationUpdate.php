<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\Catalog\Model;

/**
 * Data object with information about changes product sku.
 */
class SkuDataForReservationUpdate
{
    /**
     * @var string
     */
    private $old;

    /**
     * @var string
     */
    private $new;

    /**
     * @param string $old
     * @param string $new
     */
    public function __construct(string $old, string $new)
    {
        $this->old = $old;
        $this->new = $new;
    }

    /**
     * Get old product sku
     *
     * @return string
     */
    public function getOld() : string
    {
        return $this->old;
    }

    /**
     * Get new product sku
     *
     * @return string
     */
    public function getNew() : string
    {
        return $this->new;
    }
}
