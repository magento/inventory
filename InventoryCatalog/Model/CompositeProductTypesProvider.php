<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Catalog\Model\ProductTypes\ConfigInterface as ProductTypesConfig;
use Magento\InventoryCatalogApi\Model\CompositeProductTypesProviderInterface;

class CompositeProductTypesProvider implements CompositeProductTypesProviderInterface
{
    /**
     * @param ProductTypesConfig $productTypesConfig
     */
    public function __construct(
        private readonly ProductTypesConfig $productTypesConfig,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(): array
    {
        $compositeTypes = array_filter(
            $this->productTypesConfig->getAll(),
            fn (array $type) => ($type['composite'] ?? false) === true,
        );

        return array_keys($compositeTypes);
    }
}
