<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Creates sales channel model by given website ID
 */
class SalesChannelByWebsiteIdProvider
{
    /**
     * @var SalesChannelByWebsiteCodeProvider
     */
    private $salesChannelByWebsiteCodeProvider;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @param SalesChannelByWebsiteCodeProvider $salesChannelByWebsiteCodeProvider
     * @param WebsiteRepositoryInterface $websiteRepository
     */
    public function __construct(
        SalesChannelByWebsiteCodeProvider $salesChannelByWebsiteCodeProvider,
        WebsiteRepositoryInterface $websiteRepository
    ) {
        $this->salesChannelByWebsiteCodeProvider = $salesChannelByWebsiteCodeProvider;
        $this->websiteRepository = $websiteRepository;
    }

    /**
     * @param int $websiteId
     * @return SalesChannelInterface
     * @throws NoSuchEntityException
     */
    public function execute(int $websiteId): SalesChannelInterface
    {
        $website = $this->websiteRepository->getById($websiteId);
        return $this->salesChannelByWebsiteCodeProvider->execute($website->getCode());
    }
}
