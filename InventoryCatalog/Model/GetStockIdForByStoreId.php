<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Returns stock id for provided store id.
 */
class GetStockIdForByStoreId
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly StockResolverInterface $stockResolver
    ) {
    }

    /**
     * Returns stock id for provided store id
     *
     * @param int $storeId
     * @return int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(int $storeId): int
    {
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();

        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);

        return (int) $stock->getStockId();
    }
}
