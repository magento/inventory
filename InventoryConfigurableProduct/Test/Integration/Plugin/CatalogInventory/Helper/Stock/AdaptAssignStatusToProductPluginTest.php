<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Integration\Plugin\CatalogInventory\Helper\Stock;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\Helper\Stock\AdaptAssignStatusToProductPlugin;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;

class AdaptAssignStatusToProductPluginTest extends TestCase
{
    /**
     * @var AdaptAssignStatusToProductPlugin
     */
    private $subject;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = Bootstrap::getObjectManager()->get(AdaptAssignStatusToProductPlugin::class);
    }

    /**
     * Test that out of stock Configurable product with options, one of which is out of stock, stays Out of stock
     *
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable_12345.php
     * @return void
     */
    public function testBeforeAssignStatusToProduct(): void
    {
        $stock = Bootstrap::getObjectManager()->get(\Magento\CatalogInventory\Helper\Stock::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);

        $configurable = $productRepository->get('12345');
        $configurable->setQuantityAndStockStatus(['is_in_stock' => false]);
        $configurable->save();
        $option = $productRepository->get('simple_30');
        $option->setQuantityAndStockStatus(['is_in_stock' => false]);
        $option->save();
        $result = $this->subject->beforeAssignStatusToProduct($stock, $configurable, null);
        $this->assertEquals(null, $result[1]);
    }
}
