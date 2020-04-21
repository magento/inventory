<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model;

use InvalidArgumentException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolve website ID by store ID
 */
class StoreWebsiteResolver
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var array
     */
    private $storeWebsiteIds;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * Get website ID by store ID
     *
     * @param int $storeId store ID
     * @return int website ID
     * @throws InvalidArgumentException
     */
    public function execute(int $storeId): int
    {
        $websiteIds = $this->getWebsiteIds();
        if (!isset($websiteIds[$storeId])) {
            throw new InvalidArgumentException('Unable to resolve website ID for store ID ' . $storeId);
        }
        return $websiteIds[$storeId];
    }

    /**
     * Get storeIds with their websiteIds
     *
     * @return array
     */
    private function getWebsiteIds(): array
    {
        if ($this->storeWebsiteIds === null) {
            $this->storeWebsiteIds = [];
            foreach ($this->storeManager->getStores() as $store) {
                $this->storeWebsiteIds[(int) $store->getId()] = (int) $store->getWebsiteId();
            }
        }
        return $this->storeWebsiteIds;
    }
}
