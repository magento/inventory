<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryGroupedProduct\Test\Integration\Model;

use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use Magento\InventoryCatalogApi\Model\CompositeProductTypesProviderInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class CompositeProductTypesProviderTest extends TestCase
{
    public function testExecute(): void
    {
        $compositeProductTypesProvider = Bootstrap::getObjectManager()
            ->create(CompositeProductTypesProviderInterface::class);
        $compositeProductTypes = $compositeProductTypesProvider->execute();
        self::assertContains(GroupedType::TYPE_CODE, $compositeProductTypes);
    }
}
