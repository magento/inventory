<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventorySales\Model;

use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

class StockResolverByWebsiteId
{
    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * StockResolverByWebsiteId constructor.
     *
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param StockResolverInterface     $stockResolver
     */
    public function __construct(
        WebsiteRepositoryInterface $websiteRepository,
        StockResolverInterface $stockResolver
    ) {
        $this->websiteRepository = $websiteRepository;
        $this->stockResolver = $stockResolver;
    }

    /**
     * Resolve Stock by Website ID
     *
     * @param int $websiteId
     *
     * @return StockInterface
     */
    public function get(int $websiteId): StockInterface
    {
        $websiteCode = $this->websiteRepository->getById($websiteId)->getCode();

        return $this->stockResolver->get(
            SalesChannelInterface::TYPE_WEBSITE,
            $websiteCode
        );
    }
}
