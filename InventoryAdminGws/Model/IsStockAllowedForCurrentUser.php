<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminGws\Model;

use Magento\InventoryAdminGws\Model\ResourceModel\GetAllWebsiteCodes;
use Magento\InventoryApi\Api\Data\StockInterface;

/**
 * Verify stock allowed for current user service.
 */
class IsStockAllowedForCurrentUser
{
    /**
     * @var GetAllWebsiteCodes
     */
    private $getAllWebsiteCodes;

    /**
     * @var GetAllowedWebsiteCodes
     */
    private $allowedWebsiteCodes;

    /**
     * @var array
     */
    private $stocks = [];

    /**
     * @param GetAllWebsiteCodes $getAllWebsiteCodes
     * @param GetAllowedWebsiteCodes $allowedWebsiteCodes
     */
    public function __construct(
        GetAllWebsiteCodes $getAllWebsiteCodes,
        GetAllowedWebsiteCodes $allowedWebsiteCodes
    ) {
        $this->getAllWebsiteCodes = $getAllWebsiteCodes;
        $this->allowedWebsiteCodes = $allowedWebsiteCodes;
    }

    /**
     * Verify stock allowed for current user.
     *
     * @param StockInterface $stock
     * @return bool
     */
    public function execute(StockInterface $stock): bool
    {
        if (isset($this->stocks[$stock->getStockId()])) {
            return $this->stocks[$stock->getStockId()];
        }
        $result = true;
        $codes = $this->allowedWebsiteCodes->execute();
        $allCodes = $this->getAllWebsiteCodes->execute();
        $salesChannels = $stock->getExtensionAttributes()->getSalesChannels() ?: [];
        foreach ($salesChannels as $salesChannel) {
            if ($salesChannel->getType() === 'website'
                && !in_array($salesChannel->getCode(), $codes) && in_array($salesChannel->getCode(), $allCodes)) {
                $result = false;
                break;
            }
        }
        $this->stocks[$stock->getStockId()] = $result;

        return $result;
    }
}
