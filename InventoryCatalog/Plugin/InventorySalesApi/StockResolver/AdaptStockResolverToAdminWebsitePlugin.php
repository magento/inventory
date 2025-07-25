<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\InventorySalesApi\StockResolver;

use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Adapt Stock resolver to admin website
 */
class AdaptStockResolverToAdminWebsitePlugin
{
    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProviderInterface;

    /**
     * @param DefaultStockProviderInterface $defaultStockProviderInterface
     * @param StockRepositoryInterface $stockRepositoryInterface
     */
    public function __construct(
        DefaultStockProviderInterface $defaultStockProviderInterface,
        StockRepositoryInterface $stockRepositoryInterface
    ) {
        $this->defaultStockProviderInterface = $defaultStockProviderInterface;
        $this->stockRepository = $stockRepositoryInterface;
    }

    /**
     * Resolves stock for admin website sales channel by adapting the stock resolver to use the default stock.
     *
     * @param GetStockBySalesChannelInterface $getStockBySalesChannel
     * @param callable $proceed
     * @param SalesChannelInterface $salesChannel
     * @return StockInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        GetStockBySalesChannelInterface $getStockBySalesChannel,
        callable $proceed,
        SalesChannelInterface $salesChannel
    ): StockInterface {
        if (SalesChannelInterface::TYPE_WEBSITE === $salesChannel->getType()
            && WebsiteInterface::ADMIN_CODE === $salesChannel->getCode()) {
            return $this->stockRepository->get($this->defaultStockProviderInterface->getId());
        }
        return $proceed($salesChannel);
    }
}
