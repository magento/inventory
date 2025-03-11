<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearchBundleProduct\Test\Integration\Model\Indexer;

use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Model\Layer\Search as SearchLayer;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\App\Area;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

#[
    DbIsolation(false),
    AppArea(Area::AREA_FRONTEND),
]
class FulltextTest extends TestCase
{
    /**
     * @var Collection
     */
    private $fulltextCollection;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->fulltextCollection = Bootstrap::getObjectManager()->create(SearchLayer::class)
            ->getProductCollection();
    }

    #[
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(ProductFixture::class, as: 'p2'),
        DataFixture(ProductFixture::class, ['stock_item' => ['qty' => 0]], 'p3'),
        DataFixture(ProductFixture::class, ['status' => 2], 'p4'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$p1$', '$p3$']], 'v1o1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$p2$', '$p4$']], 'v1o2'),
        DataFixture(BundleProductFixture::class, ['sku' => 'bundle-v1', '_options' => ['$v1o1$', '$v1o2$']]),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$p1$']], 'v2o1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$p3$']], 'v2o2'),
        DataFixture(BundleProductFixture::class, ['sku' => 'bundle-v2', '_options' => ['$v2o1$', '$v2o2$']]),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$p2$']], 'v3o1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$p4$']], 'v3o2'),
        DataFixture(BundleProductFixture::class, ['sku' => 'bundle-v3', '_options' => ['$v3o1$', '$v3o2$']]),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$p3$', '$p4$']], 'v4o1'),
        DataFixture(BundleProductFixture::class, ['sku' => 'bundle-v4', '_options' => ['$v4o1$']]),
    ]
    public function testReindexBundleProducts()
    {
        $this->fulltextCollection->addSearchFilter('bundle');
        $this->fulltextCollection->load();
        $skus = $this->fulltextCollection->getColumnValues('sku');
        self::assertSame(['bundle-v1'], $skus);
    }
}
