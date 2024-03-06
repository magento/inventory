<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Provides info about product stock status or isSalable.
 */
class ProductStockStatus implements ResetAfterRequestInterface
{
    /**
     * @var array
     */
    private $stockStatus = [];

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->areProductsSalable = $areProductsSalable;
    }

    /**
     * Retrieve stock status where product is present.
     *
     * @param string $sku
     * @param int $websiteId
     * @return int
     */
    public function isProductInStock(string $sku, int $websiteId): int
    {
        if (!isset($this->stockStatus[$websiteId][$sku])) {
            $result = $this->areProductsSalable->execute([$sku], $websiteId);
            $result = current($result);
            $this->stockStatus[$websiteId][$sku] = (int)$result->isSalable();
        }
        return $this->stockStatus[$websiteId][$sku];
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->stockStatus = [];
    }
}
