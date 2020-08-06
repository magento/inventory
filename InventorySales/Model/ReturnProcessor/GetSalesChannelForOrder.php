<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventorySales\Model\ReturnProcessor;

use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

class GetSalesChannelForOrder
{
    /**
     * @var SalesChannelInterfaceFactory
     */
    private $salesChannelFactory;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param SalesChannelInterfaceFactory $salesChannelFactory
     */
    public function __construct(
        WebsiteRepositoryInterface $websiteRepository,
        SalesChannelInterfaceFactory $salesChannelFactory
    ) {
        $this->websiteRepository = $websiteRepository;
        $this->salesChannelFactory = $salesChannelFactory;
    }

    /**
     * Return sales channel for order
     *
     * @param OrderInterface $order
     * @return SalesChannelInterface
     */
    public function execute(OrderInterface $order): SalesChannelInterface
    {
        $websiteId = (int)$order->getStore()->getWebsiteId();
        $websiteCode = $this->websiteRepository->getById($websiteId)->getCode();

        return $this->salesChannelFactory->create([
            'data' => [
                'type' => SalesChannelInterface::TYPE_WEBSITE,
                'code' => $websiteCode
            ]
        ]);
    }
}
