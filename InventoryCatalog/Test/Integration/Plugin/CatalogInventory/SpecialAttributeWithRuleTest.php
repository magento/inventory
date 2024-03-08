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

namespace Magento\InventoryCatalog\Test\Integration\Plugin\CatalogInventory;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogRule\Model\ResourceModel\Product\ConditionsToCollectionApplier;
use Magento\CatalogRule\Model\Rule\Condition\Combine;
use Magento\CatalogRule\Model\Rule\Condition\CombineFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class SpecialAttributeWithRuleTest extends TestCase
{
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ConditionsToCollectionApplier
     */
    private $conditionsToCollectionApplier;

    /**
     * @var CombineFactory
     */
    private $combinedConditionFactory;

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
        $objectManager = Bootstrap::getObjectManager();
        $this->productCollectionFactory = $objectManager->get(CollectionFactory::class);
        $this->conditionsToCollectionApplier = $objectManager->get(ConditionsToCollectionApplier::class);
        $this->combinedConditionFactory = $objectManager->get(CombineFactory::class);
        $this->fixture = DataFixtureStorageManager::getStorage();
    }

    #[
        AppArea('frontend'),
        DbIsolation(false),
        DataFixture(ProductFixture::class, [
            'sku' => 'simple'
        ], 'p1'),
    ]
    /**
     * @return void
     * @throws InputException
     */
    public function testCatalogRuleForSpecialAttribute(): void
    {
        $productCollection = $this->productCollectionFactory->create();
        $product = $this->fixture->get('p1');
        $productCollection->addWebsiteFilter($product->getWebsiteIds());
        $resultCollection = $this->conditionsToCollectionApplier->applyConditionsToCollection(
            $this->getCombineCondition(),
            $productCollection
        );
        $resultSkuList = array_map(
            function (Product $product) {
                return $product->getSku();
            },
            array_values($resultCollection->getItems())
        );
        $this->assertNotEmpty($resultSkuList);
    }

    /**
     * Return combine conditions for filtering
     *
     * @return Combine
     */
    private function getCombineCondition(): Combine
    {
        $conditions = [
            'type' => Combine::class,
            'aggregator' => 'all',
            'value' => '1',
            'stop_rules_processing' => false,
            'simple_action' => 'by_fixed',
            'discount_amount' => 2,
            'conditions' => [
                [
                    'type' => \Magento\CatalogRule\Model\Rule\Condition\Product::class,
                    'attribute' => 'quantity_and_stock_status',
                    'operator' => '==',
                    'value' => '1',
                    'is_value_processed' => ''
                ]
            ],
        ];
        $combinedCondition = $this->combinedConditionFactory->create();
        $combinedCondition->setPrefix('conditions');
        $combinedCondition->loadArray($conditions);
        return $combinedCondition;
    }
}
