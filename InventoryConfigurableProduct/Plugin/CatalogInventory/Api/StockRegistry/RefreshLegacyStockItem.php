<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\Api\StockRegistry;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryConfiguration\Model\GetLegacyStockItem;

class RefreshLegacyStockItem
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var GetLegacyStockItem
     */
    private GetLegacyStockItem $getLegacyStockItem;

    /**
     * @param RequestInterface $request
     * @param GetLegacyStockItem $getLegacyStockItem
     */
    public function __construct(RequestInterface $request, GetLegacyStockItem $getLegacyStockItem)
    {
        $this->request = $request;
        $this->getLegacyStockItem = $getLegacyStockItem;
    }

    /**
     * Refresh legacy stock item.
     *
     * @param StockRegistryInterface $subject
     * @param string $productSku
     * @param StockItemInterface $stockItem
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function beforeUpdateStockItemBySku(
        StockRegistryInterface $subject,
        string $productSku,
        StockItemInterface $stockItem
    ): array {
        $this->getLegacyStockItem->execute($productSku);
        return [$productSku, $stockItem];
    }
}
