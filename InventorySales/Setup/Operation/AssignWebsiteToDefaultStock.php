<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Setup\Operation;

use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Assigns Main website to the Default stock
 */
class AssignWebsiteToDefaultStock
{
    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var SalesChannelInterfaceFactory
     */
    private $salesChannelFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Initialize dependencies
     *
     * @param StockRepositoryInterface $stockRepository
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param SalesChannelInterfaceFactory $salesChannelFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StockRepositoryInterface $stockRepository,
        DefaultStockProviderInterface $defaultStockProvider,
        SalesChannelInterfaceFactory $salesChannelFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->stockRepository = $stockRepository;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->salesChannelFactory = $salesChannelFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Assign default website to the default stock
     *
     * @param string|null $websiteCode
     */
    public function execute(string $websiteCode = null)
    {
        if ($websiteCode === null) {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
        }

        $defaultStockId = $this->defaultStockProvider->getId();
        $defaultStock = $this->stockRepository->get($defaultStockId);

        $extensionAttributes = $defaultStock->getExtensionAttributes();
        $salesChannels = $extensionAttributes->getSalesChannels();
        $salesChannels[] = $this->createSalesChannelByWebsiteCode($websiteCode);

        $extensionAttributes->setSalesChannels($salesChannels);
        $this->stockRepository->save($defaultStock);
    }

    /**
     * Create the sales channel by given website code
     *
     * @param string $websiteCode
     * @return SalesChannelInterface
     */
    private function createSalesChannelByWebsiteCode(string $websiteCode): SalesChannelInterface
    {
        $salesChannel = $this->salesChannelFactory->create();
        $salesChannel->setCode($websiteCode);
        $salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
        return $salesChannel;
    }
}
