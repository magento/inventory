<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Model\ResourceModel;

use Magento\CatalogInventory\Model\ResourceModel\StockStatusFilterInterface;
use Magento\Framework\DB\Select;
use Magento\InventoryCatalog\Model\ResourceModel\StockStatusFilter;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Legacy in-stock status filter plugin
 */
class StockStatusFilterPlugin
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var StockResolverInterface
     */
    private $stockResolver;
    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;
    /**
     * @var StockStatusFilter
     */
    private $stockStatusFilter;

    /**
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param StockStatusFilter $stockStatusFilter
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        DefaultStockProviderInterface $defaultStockProvider,
        StockStatusFilter $stockStatusFilter
    ) {
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->stockStatusFilter = $stockStatusFilter;
    }

    /**
     * Add in-stock status constraint to the select for non default stock
     *
     * @param StockStatusFilterInterface $subject
     * @param callable $proceed
     * @param Select $select
     * @param string $productTableAlias
     * @param string $stockStatusTableAlias
     * @param int|null $websiteId
     * @return Select
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        StockStatusFilterInterface $subject,
        callable $proceed,
        Select $select,
        string $productTableAlias,
        string $stockStatusTableAlias,
        ?int $websiteId = null
    ): Select {
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $stockId = (int)$stock->getStockId();
        if ($this->defaultStockProvider->getId() === $stockId) {
            $select = $proceed(
                $select,
                $productTableAlias,
                $stockStatusTableAlias,
                $websiteId
            );
        } else {
            $select = $this->stockStatusFilter->execute(
                $select,
                $productTableAlias,
                $stockStatusTableAlias,
                $stockId
            );
        }
        return $select;
    }
}
