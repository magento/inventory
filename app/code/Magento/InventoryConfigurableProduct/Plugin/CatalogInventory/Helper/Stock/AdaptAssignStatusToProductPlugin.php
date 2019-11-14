<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\Helper\Stock;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Stock;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

/**
 * Process configurable product stock status considering configurable options salable status.
 */
class AdaptAssignStatusToProductPlugin
{
    /**
     * @var Configurable
     */
    private $configurable;

    /**
     * @param Configurable $configurable
     */
    public function __construct(Configurable $configurable)
    {
        $this->configurable = $configurable;
    }

    /**
     * Process configurable product stock status, considering configurable options.
     *
     * Configurable options will be validated with 'is salable' chain. Not salable options will be removed.
     * @see \Magento\InventoryConfigurableProduct\Plugin\Model\AttributeOptionProvider\IsSalableOptionPlugin
     *
     * @param Stock $subject
     * @param Product $product
     * @param int|null $status
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAssignStatusToProduct(
        Stock $subject,
        Product $product,
        $status = null
    ): array {
        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            $status = !empty(current($this->configurable->getConfigurableOptions($product)));
        }

        return [$product, (int)$status];
    }
}
