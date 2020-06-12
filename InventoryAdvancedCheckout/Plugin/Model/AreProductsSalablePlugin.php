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
use Magento\Framework\Exception\NoSuchEntityException;
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
     * @param ProductRepositoryInterface $productRepository
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
        $this->productRepository = $productRepository;
    }

    /**
     * Get products salable status for given sku requests.
     *
     * @param AreProductsSalableForRequestedQtyInterface $subject
     * @param callable $proceed
     * @param ProductQuantity[] $productQuantities
     * @param int $websiteId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws NoSuchEntityException in case product doesn't exists
     */
    public function aroundExecute(
        AreProductsSalableForRequestedQtyInterface $subject,
        callable $proceed,
        array $productQuantities,
        int $websiteId
    ): array {
        $result = [];
        foreach ($productQuantities as $productQuantity) {
            $product = $this->productRepository->get($productQuantity->getSku());
            $result[] = $this->objectManager->create(
                IsProductsSalableForRequestedQtyResult::class,
                ['sku' => $product->getSku(), 'isSalable' => $product->isSalable()]
            );
        }

        return $result;
    }
}
