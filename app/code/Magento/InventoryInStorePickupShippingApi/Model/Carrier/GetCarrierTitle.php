<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingApi\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Get In-Store Pickup carrier title
 *
 * @api
 */
class GetCarrierTitle
{
    private const CONFIG_PATH = 'carriers/in_store/title';

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
     * @param int|null $storeId
     *
     * @return string
     */
    public function execute(?int $storeId = null): string
    {
        return $storeId ?
            $this->scopeConfig->getValue(self::CONFIG_PATH, ScopeInterface::SCOPE_STORE, $storeId)
            : $this->scopeConfig->getValue(self::CONFIG_PATH);
    }
}
