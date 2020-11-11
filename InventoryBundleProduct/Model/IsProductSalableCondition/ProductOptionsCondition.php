<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model\IsProductSalableCondition;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\InventoryBundleProduct\Model\IsBundleProductSalable;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

/**
 * Verify bundle product options salable status.
 */
class ProductOptionsCondition implements IsProductSalableInterface
{
    /**
     * @var Type
     */
    private $type;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var IsBundleProductSalable
     */
    private $isBundleProductSalable;

    /**
     * @param Type $type
     * @param ProductRepositoryInterface $productRepository
     * @param IsBundleProductSalable $isBundleProductSalable
     */
    public function __construct(
        Type $type,
        ProductRepositoryInterface $productRepository,
        IsBundleProductSalable $isBundleProductSalable
    ) {
        $this->type = $type;
        $this->productRepository = $productRepository;
        $this->isBundleProductSalable = $isBundleProductSalable;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $product = $this->productRepository->get($sku);
        if ($product->getTypeId() !== Type::TYPE_CODE) {
            return true;
        }
        $options = $this->type->getOptionsCollection($product);

        return $this->isBundleProductSalable->execute($product, $options->getItems(), $stockId);
    }
}
