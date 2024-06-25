<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogRule\Test\Integration\Plugin;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogRule\Model\Rule\Condition\Combine;
use Magento\CatalogRule\Model\Rule\Condition\Product;
use Magento\CatalogRule\Test\Fixture\Rule as CatalogRuleFixture;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use PHPUnit\Framework\TestCase;

class ValidateProductSpecialAttributeTest extends TestCase
{
    /**
     * @var DataFixtureStorage
     */
    private $fixture;

    /**
     * @return void
     * @throws LocalizedException
     */
    protected function setUp(): void
    {
        $this->fixture = DataFixtureStorageManager::getStorage();
    }

    #[
        AppArea('frontend'),
        DbIsolation(false),
        DataFixture(CatalogRuleFixture::class, [
            'is_active' => true,
            'simple_action' => 'by_fixed',
            'discount_amount' => 2,
            'stop_rules_processing' => false,
            'conditions' => [
                'type' => Combine::class,
                'aggregator' => 'all',
                'value' => '1',
                'conditions' => [
                    [
                        'type' => Product::class,
                        'attribute' => 'quantity_and_stock_status',
                        'operator' => '==',
                        'value' => '1',
                        'is_value_processed' => ''
                    ]
                ]
            ],
        ], 'rule1'),
        DataFixture(ProductFixture::class, [
            'sku' => 'p1',
            'extension_attributes' => [
                'website_ids' => [1],
                'stock_item' => [
                    'is_in_stock' => true,
                ]
            ],
        ], 'p1'),
        DataFixture(ProductFixture::class, [
            'sku' => 'p2',
            'extension_attributes' => [
                'website_ids' => [1],
                'stock_item' => [
                    'is_in_stock' => false,
                ]
            ],
        ], 'p2'),
    ]
    /**
     * @return void
     * @throws InputException
     */
    public function testCatalogRuleForSpecialAttributeQuantityAndStockStatus(): void
    {
        $r1 = $this->fixture->get('rule1');
        $p1 = $this->fixture->get('p1');
        $p2 = $this->fixture->get('p2');
        $productIds = $r1->getMatchingProductIds();
        $this->assertArrayHasKey(1, $productIds[$p1->getId()]);
        $this->assertTrue($productIds[$p1->getId()][1]);
        $this->assertFalse($productIds[$p2->getId()][1]);
        $this->assertCount(2, $productIds);
    }
}
