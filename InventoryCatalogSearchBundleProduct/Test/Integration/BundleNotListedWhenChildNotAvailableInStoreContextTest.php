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

namespace Magento\InventoryCatalogSearchBundleProduct\Test\Integration;

use Magento\Bundle\Test\Fixture\Link as BundleSelectionFixture;
use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Model\Layer\Search;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\Exception\LocalizedException;
use Magento\Indexer\Model\Indexer;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Test\Fixture\Group as StoreGroupFixture;
use Magento\Store\Test\Fixture\Store as StoreFixture;
use Magento\Store\Test\Fixture\Website as WebsiteFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class BundleNotListedWhenChildNotAvailableInStoreContextTest extends TestCase
{
    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->indexer = Bootstrap::getObjectManager()->create(
            Indexer::class
        );
        $this->indexer->load('catalogsearch_fulltext');

        $this->queryFactory = Bootstrap::getObjectManager()->get(
            QueryFactory::class
        );

        parent::setUp();
    }

    #[
        DbIsolation(false),
        DataFixture(WebsiteFixture::class, as: 'website2'),
        DataFixture(StoreGroupFixture::class, ['website_id' => '$website2.id$'], 'group2'),
        DataFixture(StoreFixture::class, ['store_group_id' => '$group2.id$', 'code' => 'store2'], 'store2'),
        DataFixture(ProductFixture::class, ['sku' => 'simple1', 'website_ids' => [1, '$website2.id']], 's1'),
        DataFixture(ProductFixture::class, ['sku' => 'simple2', 'website_ids' => [1, '$website2.id']], 's2'),
        DataFixture(ProductFixture::class, ['sku' => 'simple3', 'website_ids' => ['$website2.id']], 's3'),
        DataFixture(BundleSelectionFixture::class, ['sku' => '$s1.sku$'], 'link1'),
        DataFixture(BundleSelectionFixture::class, ['sku' => '$s2.sku$'], 'link2'),
        DataFixture(BundleSelectionFixture::class, ['sku' => '$s3.sku$'], 'link3'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$link1$']], 'opt1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$link2$']], 'opt2'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$link3$']], 'opt3'),
        DataFixture(
            BundleProductFixture::class,
            ['sku' => 'bundle1', '_options' => ['$opt1$', '$opt2$', '$opt3$'], 'website_ids' => [1, '$website2.id']]
        ),
    ]
    /**
     * @return void
     * @throws LocalizedException
     * @throws \Throwable
     */
    public function testBundleNotReturnedIfChildIsNotAvailableInStore(): void
    {
        $this->indexer->reindexAll();

        $products = $this->search('bundle1');
        $this->assertCount(0, $products);
    }

    /**
     * Search the text and return result collection
     *
     * @param string $text
     * @return Product[]
     * @throws LocalizedException
     */
    private function search(string $text): array
    {
        $query = $this->queryFactory->get();
        $query->unsetData();
        $query->setQueryText($text);
        $query->saveIncrementalPopularity();
        $products = [];
        $searchLayer = Bootstrap::getObjectManager()->create(Search::class);
        $collection = $searchLayer->getProductCollection();
        $collection->addSearchFilter($text);

        foreach ($collection as $product) {
            $products[] = $product;
        }
        return $products;
    }
}
