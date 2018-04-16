<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Get Sales Channel for current Website.
 */
class GetActualSalesChannel
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @var SalesChannelInterfaceFactory
     */
    private $salesChannelFactory;

    /**
     * @param StoreManagerInterface $storeManager
     * @param StockRepositoryInterface $stockRepository
     * @param SalesChannelInterfaceFactory $salesChannelFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        StockRepositoryInterface $stockRepository,
        SalesChannelInterfaceFactory $salesChannelFactory
    ) {
        $this->storeManager = $storeManager;
        $this->stockRepository = $stockRepository;
        $this->salesChannelFactory = $salesChannelFactory;
    }

    /**
     * Get Sales Channel for current Website.
     */
    public function execute(int $stockId): SalesChannelInterface
    {
        $websiteCode = $this->storeManager->getWebsite()->getCode();
        $stock = $this->stockRepository->get($stockId);
        $salesChannels = $stock->getExtensionAttributes()->getSalesChannels();
        $actualChannel = null;
        foreach ($salesChannels as $salesChannel) {
            if ($salesChannel->getCode() === $websiteCode) {
                $actualChannel = $salesChannel;
                break;
            }
        }
        if (null === $actualChannel) {
            $actualChannel = $this->salesChannelFactory->create();
            $actualChannel->setCode($websiteCode);
            $actualChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
        }

        return $actualChannel;
    }
}
