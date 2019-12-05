<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySetupFixtureGenerator\Plugin\Setup\Fixtures;

use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Model\ReplaceSalesChannelsForStockInterface;
use Magento\Setup\Fixtures\StoresFixture;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Assign all websites to default stock after generating website fixtures.
 */
class StoresFixturePlugin
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ReplaceSalesChannelsForStockInterface
     */
    private $replaceSalesChannelsForStock;

    /**
     * @var SalesChannelInterfaceFactory
     */
    private $salesChannelInterfaceFactory;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ReplaceSalesChannelsForStockInterface $replaceSalesChannelsForStock
     * @param SalesChannelInterfaceFactory $salesChannelInterfaceFactory
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ReplaceSalesChannelsForStockInterface $replaceSalesChannelsForStock,
        SalesChannelInterfaceFactory $salesChannelInterfaceFactory,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->storeManager = $storeManager;
        $this->replaceSalesChannelsForStock = $replaceSalesChannelsForStock;
        $this->salesChannelInterfaceFactory = $salesChannelInterfaceFactory;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * Assign all websites to default stock after generating website fixtures.
     *
     * @param StoresFixture $subject
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(StoresFixture $subject)
    {
        $salesChannels = [];
        $websites = $this->storeManager->getWebsites();
        foreach ($websites as $website) {
            $salesChannels[] = $this->salesChannelInterfaceFactory->create(
                [
                    'data' => [
                        SalesChannelInterface::TYPE => SalesChannelInterface::TYPE_WEBSITE,
                        SalesChannelInterface::CODE => $website->getCode()
                    ]
                ]
            );
        }

        $this->replaceSalesChannelsForStock->execute($salesChannels, $this->defaultStockProvider->getId());
    }
}
