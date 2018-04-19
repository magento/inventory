<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySales\Model\SalesChannelByWebsiteCodeProvider;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Service for get sales channel model for current website.
 */
class GetSalesChannelForCurrentWebsite
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SalesChannelByWebsiteCodeProvider
     */
    private $salesChannelByWebsiteCodeProvider;

    /**
     * @param StoreManagerInterface $storeManager
     * @param SalesChannelByWebsiteCodeProvider $salesChannelByWebsiteCodeProvider
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        SalesChannelByWebsiteCodeProvider $salesChannelByWebsiteCodeProvider
    ) {
        $this->storeManager = $storeManager;
        $this->salesChannelByWebsiteCodeProvider = $salesChannelByWebsiteCodeProvider;
    }

    /**
     * @return SalesChannelInterface
     * @throws LocalizedException
     */
    public function execute(): SalesChannelInterface
    {
        $websiteCode = $this->storeManager->getWebsite()->getCode();
        return $this->salesChannelByWebsiteCodeProvider->execute($websiteCode);
    }
}
