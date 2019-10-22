<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InventoryInStorePickup\Model\ResourceModel\IsStorePickUpAvailableForStock;
use Magento\InventorySalesApi\Model\GetAssignedStockIdForWebsiteInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Verify, is store pickup available for given website.
 */
class IsStorePickupAvailableForWebsite
{
    const CONFIG_PATH = 'carriers/in_store/active';

    /**
     * @var GetAssignedStockIdForWebsiteInterface
     */
    private $getAssignedStockIdForWebsite;

    /**
     * @var IsStorePickUpAvailableForStock
     */
    private $availableForStock;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @param GetAssignedStockIdForWebsiteInterface $getAssignedStockIdForWebsite
     * @param IsStorePickUpAvailableForStock $availableForStock
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        GetAssignedStockIdForWebsiteInterface $getAssignedStockIdForWebsite,
        IsStorePickUpAvailableForStock $availableForStock,
        ScopeConfigInterface $config
    ) {
        $this->getAssignedStockIdForWebsite = $getAssignedStockIdForWebsite;
        $this->availableForStock = $availableForStock;
        $this->config = $config;
    }

    /**
     * Get store pickup is available for given website.
     *
     * @param string $websiteCode
     * @return bool
     */
    public function execute(string $websiteCode): bool
    {
        if (!$this->config->getValue(self::CONFIG_PATH, ScopeInterface::SCOPE_WEBSITE, $websiteCode)) {
            return false;
        }
        $stockId = $this->getAssignedStockIdForWebsite->execute($websiteCode);

        return $this->availableForStock->execute($stockId);
    }
}
