<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\Product\Type;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\InventoryCatalog\Model\IsProductSalable;

class AfterGetIsSaleable
{
    /**
     * @var IsProductSalable
     */
    private $isProductSalable;

    /**
     * @param IsProductSalable $isProductSalable
     */
    public function __construct(
        IsProductSalable $isProductSalable
    ) {
        $this->isProductSalable = $isProductSalable;
    }

    /**
     * @param AbstractType $subject
     * @param callable $proceed
     * @param Product $product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return bool
     */
    public function aroundIsSalable(AbstractType $subject, callable $proceed, $product): bool
    {
        return $this->isProductSalable->execute($product);
    }
}
