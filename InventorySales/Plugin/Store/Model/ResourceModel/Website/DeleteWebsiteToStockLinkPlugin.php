<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\Store\Model\ResourceModel\Website;

use Magento\Framework\Model\AbstractModel;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Model\DeleteSalesChannelToStockLinkInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ResourceModel\Website as WebsiteResourceModel;
use Magento\Store\Model\Website;

/**
 * Delete link between Stock and Website
 */
class DeleteWebsiteToStockLinkPlugin
{
    /**
     * @var DeleteSalesChannelToStockLinkInterface
     */
    private $deleteSalesChannel;

    /**
     * @param DeleteSalesChannelToStockLinkInterface $deleteSalesChannel
     */
    public function __construct(
        DeleteSalesChannelToStockLinkInterface $deleteSalesChannel
    ) {
        $this->deleteSalesChannel = $deleteSalesChannel;
    }

    /**
     * Deletes the link between a stock and a website by removing the associated sales channel after deletion.
     *
     * @param WebsiteResourceModel $subject
     * @param WebsiteResourceModel $result
     * @param Website|AbstractModel $website
     * @return WebsiteResourceModel
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDelete(
        WebsiteResourceModel $subject,
        WebsiteResourceModel $result,
        AbstractModel $website
    ) {
        $websiteCode = $website->getCode();

        if ($websiteCode !== WebsiteInterface::ADMIN_CODE) {
            $this->deleteSalesChannel->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        }
        return $result;
    }
}
