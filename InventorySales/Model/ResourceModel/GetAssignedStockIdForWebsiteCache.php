<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\InventorySalesApi\Model\GetAssignedStockIdForWebsiteInterface;

/**
 * @inheritdoc
 */
class GetAssignedStockIdForWebsiteCache implements GetAssignedStockIdForWebsiteInterface, ResetAfterRequestInterface
{
    /**
     * @var GetAssignedStockIdForWebsite
     */
    private $getAssignedStockIdForWebsite;

    /**
     * @var int[]
     */
    private $stockIds = [];

    /**
     * @param GetAssignedStockIdForWebsite $getAssignedStockIdForWebsite
     */
    public function __construct(
        GetAssignedStockIdForWebsite $getAssignedStockIdForWebsite
    ) {
        $this->getAssignedStockIdForWebsite = $getAssignedStockIdForWebsite;
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->stockIds = [];
    }

    /**
     * @inheritdoc
     */
    public function execute(string $websiteCode): ?int
    {
        if (!isset($this->stockIds[$websiteCode])) {
            $this->stockIds[$websiteCode] = $this->getAssignedStockIdForWebsite->execute($websiteCode);
        }

        return $this->stockIds[$websiteCode];
    }
}
