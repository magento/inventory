<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Observer\Website;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventorySales\Model\ResourceModel\UpdateSalesChannelsWebsiteCode;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\Website;

/**
 * Updates "code" column values in inventory_stock_sales_channel table if corresponding Website Code was changed.
 */
class UpdateStockSalesChannelLinks implements ObserverInterface
{
    /**
     * @var UpdateSalesChannelsWebsiteCode
     */
    private $updateSalesChannelsWebsiteCode;

    /**
     * @param UpdateSalesChannelsWebsiteCode $updateSalesChannelsWebsiteCode
     */
    public function __construct(
        UpdateSalesChannelsWebsiteCode $updateSalesChannelsWebsiteCode
    ) {
        $this->updateSalesChannelsWebsiteCode = $updateSalesChannelsWebsiteCode;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        /** @var Website $website */
        $website = $observer->getData('website');
        $websiteCode = $website->getCode();

        if ($websiteCode === WebsiteInterface::ADMIN_CODE) {
            return;
        }

        if (isset($website->getStoredData()['code'])) {
            $oldWebsiteCode = $website->getStoredData()['code'];
            if ($websiteCode !== $oldWebsiteCode) {
                $this->updateSalesChannelsWebsiteCode->execute($websiteCode, $oldWebsiteCode);
            }
        }
    }
}
