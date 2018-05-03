<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;

/**
 * Creates sales channel model by given website code
 */
class SalesChannelByWebsiteCodeProvider
{
    /**
     * @var SalesChannelInterfaceFactory
     */
    private $salesChannelFactory;

    /**
     * @param SalesChannelInterfaceFactory $salesChannelFactory
     */
    public function __construct(
        SalesChannelInterfaceFactory $salesChannelFactory
    ) {
        $this->salesChannelFactory = $salesChannelFactory;
    }

    /**
     * @param string $websiteCode
     * @return SalesChannelInterface
     */
    public function execute(string $websiteCode): SalesChannelInterface
    {
        $salesChannel = $this->salesChannelFactory->create();
        $salesChannel->setCode($websiteCode);
        $salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
        return $salesChannel;
    }
}
