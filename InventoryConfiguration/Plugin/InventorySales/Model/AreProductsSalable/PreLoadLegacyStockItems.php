<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Plugin\InventorySales\Model\AreProductsSalable;

use Magento\InventoryConfiguration\Model\GetLegacyStockItems;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * This plugin is to load the legacy stock items ahead of time,  at once,  to save time.
 * Without it,  the legacy stock items are loaded one at a time, which takes significant time.
 *
 */
class PreLoadLegacyStockItems
{
    /**
     * @var GetLegacyStockItems
     */
    private $getLegacyStockItems;

    /**
     * @param GetLegacyStockItems $getLegacyStockItems
     */
    public function __construct(GetLegacyStockItems $getLegacyStockItems)
    {
        $this->getLegacyStockItems = $getLegacyStockItems;
    }

    /**
     * @inheritdoc
     */
    public function beforeExecute(AreProductsSalableInterface $subject, array $skus, int $stockId): void
    {
        $this->getLegacyStockItems->execute($skus);
    }
}
