<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\Catalog\Model\ProductRepository;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

class StatusCondition implements IsProductSalableInterface
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * StatusCondition constructor.
     * @param ProductRepository $productRepository
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $product = $this->productRepository->get($sku);
        return $product->getStatus() === \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
    }
}
