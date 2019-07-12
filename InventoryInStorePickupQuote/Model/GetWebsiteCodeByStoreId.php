<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Provide website code by store id.
 */
class GetWebsiteCodeByStoreId
{
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * GetWebsiteCodeByStoreId constructor.
     *
     * @param StoreRepositoryInterface $storeRepository
     * @param WebsiteRepositoryInterface $websiteRepository
     */
    public function __construct(
        StoreRepositoryInterface $storeRepository,
        WebsiteRepositoryInterface $websiteRepository
    ) {
        $this->storeRepository = $storeRepository;
        $this->websiteRepository = $websiteRepository;
    }

    /**
     * Get Website Code by provided Store Id.
     *
     * @param int $storeId
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function execute(int $storeId)
    {
        $store = $this->storeRepository->getById($storeId);
        $website = $this->websiteRepository->getById($store->getWebsiteId());

        return $website->getCode();
    }
}
