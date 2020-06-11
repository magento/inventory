<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdvancedCheckout\Plugin\Model;

use Magento\AdvancedCheckout\Model\AreProductsSalableForRequestedQtyInterface;
use Magento\AdvancedCheckout\Model\Data\IsProductsSalableForRequestedQtyResult;
use Magento\AdvancedCheckout\Model\Data\ProductQuantity;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\ObjectManagerInterface;

/**
 * Provides multi-sourcing capabilities for Advanced Checkout Order By SKU feature.
 */
class AreProductsSalablePlugin
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductRepositoryInterface $productRepository
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get is product out of stock for given Product id in a given Website id in MSI context.
     *
     * @param AreProductsSalableForRequestedQtyInterface $subject
     * @param callable $proceed
     * @param ProductQuantity[] $productQuantities
     * @param int $websiteId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        AreProductsSalableForRequestedQtyInterface $subject,
        callable $proceed,
        array $productQuantities,
        int $websiteId
    ): array {
        $skus = [];
        foreach ($productQuantities as $productQuantity) {
            $skus[] = $productQuantity->getSku();
        }
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('sku', $skus, 'in')->create();
        $products = $this->productRepository->getList($searchCriteria);
        $result = [];
        foreach ($products->getItems() as $product) {
            $result[] = $this->objectManager->create(
                IsProductsSalableForRequestedQtyResult::class,
                ['sku' => $product->getSku(), 'isSalable' => $product->isSalable()]
            );
        }

        return $result;
    }
}
