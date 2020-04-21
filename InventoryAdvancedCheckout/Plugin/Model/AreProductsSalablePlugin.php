<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdvancedCheckout\Plugin\Model;

use Magento\AdvancedCheckout\Model\AreProductsSalableForRequestedQtyInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Provides multi-sourcing capabilities for Advanced Checkout Order By SKU feature.
 */
class AreProductsSalablePlugin
{
    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @param AreProductsSalableInterface $areProductsSalable
     * @param StockResolverInterface $stockResolver
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        AreProductsSalableInterface $areProductsSalable,
        StockResolverInterface $stockResolver,
        WebsiteRepositoryInterface $websiteRepository,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->areProductsSalable = $areProductsSalable;
        $this->stockResolver = $stockResolver;
        $this->websiteRepository = $websiteRepository;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * Get is product out of stock for given Product id in a given Website id in MSI context.
     *
     * @param AreProductsSalableForRequestedQtyInterface $subject
     * @param callable $proceed
     * @param \Magento\AdvancedCheckout\Model\Data\ProductQuantity[] $productQuantities
     * @param int $websiteId
     * @return bool
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        AreProductsSalableForRequestedQtyInterface $subject,
        callable $proceed,
        array $productQuantities,
        int $websiteId
    ): bool {
        $website = $this->websiteRepository->getById($websiteId);
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
        if ($this->defaultStockProvider->getId() === $stock->getStockId()) {
            return $proceed($productQuantities, $websiteId);
        }

        $skus = [];
        foreach ($productQuantities as $productQuantity) {
            $skus[] = $productQuantity->getSku();
        }
        $result = $this->areProductsSalable->execute($skus, $stock->getStockId());
        $result = current($result);

        return $result->isSalable();
    }
}
