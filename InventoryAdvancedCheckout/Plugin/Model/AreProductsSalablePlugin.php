<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdvancedCheckout\Plugin\Model;

use Magento\AdvancedCheckout\Model\AreProductsSalableForRequestedQtyInterface;
use Magento\AdvancedCheckout\Model\Data\IsProductsSalableForRequestedQtyResultFactory;
use Magento\AdvancedCheckout\Model\Data\ProductQuantity;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Provides multi-sourcing capabilities for Advanced Checkout Order By SKU feature.
 */
class AreProductsSalablePlugin
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var IsProductsSalableForRequestedQtyResultFactory
     */
    private $isProductsSalableForRequestedQtyResultFactory;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param IsProductsSalableForRequestedQtyResultFactory $isProductsSalableForRequestedQtyResultFactory
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        IsProductsSalableForRequestedQtyResultFactory $isProductsSalableForRequestedQtyResultFactory
    ) {
        $this->productRepository = $productRepository;
        $this->isProductsSalableForRequestedQtyResultFactory = $isProductsSalableForRequestedQtyResultFactory;
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
     */
    public function aroundExecute(
        AreProductsSalableForRequestedQtyInterface $subject,
        callable $proceed,
        array $productQuantities,
        int $websiteId
    ): array {
        $result = [];
        foreach ($productQuantities as $productQuantity) {
            try {
                $product = $this->productRepository->get($productQuantity->getSku());
                $result[] = $this->isProductsSalableForRequestedQtyResultFactory->create(
                    ['sku' => $product->getSku(), 'isSalable' => $product->isSalable()]
                );
            } catch (NoSuchEntityException $e) {
                $result[] = $this->isProductsSalableForRequestedQtyResultFactory->create(
                    ['sku' => $productQuantity->getSku(), 'isSalable' => false]
                );
            }
        }

        return $result;
    }
}
