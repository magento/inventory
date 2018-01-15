<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Integration\SourceItems;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\GetProductQuantityInStockInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryCatalog\Api\DefaultStockProviderInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class SourceItemsReindexAfterSourceItemsChangeTest
 */
class SourceItemsReindexAfterSourceItemsChangeTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var GetProductQuantityInStockInterface
     */
    private $getProductQuantityInStock;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    protected function setUp()
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var ProductInterfaceFactory $productFactory */
        $productFactory = $objectManager->get(ProductInterfaceFactory::class);

        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);

        $this->getProductQuantityInStock = $objectManager->get(GetProductQuantityInStockInterface::class);

        $this->defaultStockProvider = $objectManager->get(DefaultStockProviderInterface::class);

        $product = $productFactory->create();
        $product->setTypeId(Type::TYPE_SIMPLE)->setAttributeSetId(4)->setName('Simple Product 1')->setSku(
            'SKU-simple-product-reindex'
        )->setPrice(10)->setStockData(
            [
                'qty' => 5.5,
                'is_in_stock' => true,
                'manage_stock' => true
            ]
        )->setStatus(Status::STATUS_ENABLED);
        $this->productRepository->save($product);
    }

    public function testReindexStockItemsDataAfterStockItemsSave()
    {
        self::assertEquals(
            5.5,
            $this->getProductQuantityInStock->execute(
                'SKU-simple-product-reindex',
                $this->defaultStockProvider->getId()
            )
        );
    }

    public function testReindexStockItemsDataAfterStockItemsDelete()
    {
        self::assertEquals(
            5.5,
            $this->getProductQuantityInStock->execute(
                'SKU-simple-product-reindex',
                $this->defaultStockProvider->getId()
            )
        );

        $objectManager = Bootstrap::getObjectManager();

        /** @var SourceItemRepositoryInterface $sourceItemRepository */
        $sourceItemRepository = $objectManager->get(SourceItemRepositoryInterface::class);
        /** @var SourceItemsDeleteInterface $sourceItemsDelete */
        $sourceItemsDelete = $objectManager->get(SourceItemsDeleteInterface::class);
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);

        $searchCriteria = $searchCriteriaBuilder->addFilter(
            SourceItemInterface::SKU,
            'SKU-simple-product-reindex'
        )->create();
        $sourceItems = $sourceItemRepository->getList($searchCriteria)->getItems();

        $sourceItemsDelete->execute($sourceItems);

        self::assertEquals(
            0,
            $this->getProductQuantityInStock->execute('SKU-simple-product-reindex', $this->defaultStockProvider->getId())
        );
    }
}
