<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductRepository;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

/**
 * Is product enabled condition.
 */
class StatusCondition implements IsProductSalableInterface
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
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
        return (int)$this->productRepository->get($sku)->getStatus() === Status::STATUS_ENABLED;
    }
}
