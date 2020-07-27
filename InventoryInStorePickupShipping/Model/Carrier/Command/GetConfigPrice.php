<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShipping\Model\Carrier\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Get In-Store Pickup carrier price
 */
class GetConfigPrice
{
    private const CONFIG_PATH = 'carriers/instore/price';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get In-Store Pickup carrier price
     *
     * @param int|null $storeId
     * @return float
     */
    public function execute(?int $storeId = null): float
    {
        return (float)($storeId ?
            $this->scopeConfig->getValue(self::CONFIG_PATH, ScopeInterface::SCOPE_STORE, $storeId)
            : $this->scopeConfig->getValue(self::CONFIG_PATH));
    }
}
