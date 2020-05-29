<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\InventoryCatalog\Model\IsProductSalable;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventoryCatalog\Model\IsProductSalable;

/**
 * Verify configurable product salable status.
 */
class IsConfigurableProductSalablePlugin
{
    /**
     * @var Configurable
     */
    private $type;

    /**
     * @param Configurable $type
     */
    public function __construct(Configurable $type)
    {
        $this->type = $type;
    }

    /**
     * Get configurable product status.
     *
     * @param IsProductSalable $subject
     * @param bool $result
     * @param ProductInterface $product
     * @return bool
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(IsProductSalable $subject, bool $result, ProductInterface $product): bool
    {
        if ($product->getTypeId() !== Configurable::TYPE_CODE || !$result) {
            return $result;
        }
        $options = $this->type->getConfigurableOptions($product);

        return !empty($options);
    }
}
