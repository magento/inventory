<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Integration\Model;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
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
        self::assertContains(ConfigurableType::TYPE_CODE, $compositeProductTypes);
    }
}
