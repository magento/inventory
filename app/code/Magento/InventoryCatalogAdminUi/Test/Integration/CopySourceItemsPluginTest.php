<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Catalog\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Copier;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test Source Items after product duplicating.
 *
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 * @magentoAppArea adminhtml
 */
class CopySourceItemsPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Copier
     */
    private $copier;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceItemsDeleteInterface
     */
    private $sourceItemsDelete;

    /**
     * Test Source Items after product duplicating in Single Source mode.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     *
     * @dataProvider duplicateInSingleSourceModeDataProvider
     * @param string $productSku
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function testDuplicateInSingleSourceMode(string $productSku): void
    {
        $product = $this->productRepository->get($productSku);
        $duplicate = $this->copier->copy($product);
        $duplicateSku = $duplicate->getSku();

        try {
            $this->assertNotEquals(
                $productSku,
                $duplicateSku
            );

            $searchCriteriaDuplicate = $this->searchCriteriaBuilder
                ->addFilter(SourceItemInterface::SKU, $duplicateSku)
                ->addFilter(SourceItemInterface::SOURCE_CODE, 'default')
                ->create();
            $duplicateSourceItems = $this->sourceItemRepository->getList($searchCriteriaDuplicate)->getItems();

            $this->assertCount(
                1,
                $duplicateSourceItems
            );

            $duplicateSourceItem = current($duplicateSourceItems);

            $this->assertEquals(
                0,
                $duplicateSourceItem->getStatus()
            );
            $this->assertEquals(
                0,
                $duplicateSourceItem->getQuantity()
            );
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $this->rollbackDuplicate($duplicate);
        }
    }

    /**
     * Test Source Items after product duplicating in Multi Source mode.
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     *
     * @dataProvider duplicateInMultiSourceModeDataProvider
     * @param string $productSku
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function testDuplicateInMultiSourceMode(string $productSku): void
    {
        $product = $this->productRepository->get($productSku);
        $duplicate = $this->copier->copy($product);
        $duplicateSku = $duplicate->getSku();

        try {
            $this->assertNotEquals(
                $productSku,
                $duplicateSku
            );

            $searchCriteriaProduct = $this->searchCriteriaBuilder
                ->addFilter(SourceItemInterface::SKU, $productSku)
                ->create();
            $searchCriteriaDuplicate = $this->searchCriteriaBuilder
                ->addFilter(SourceItemInterface::SKU, $duplicateSku)
                ->create();
            $productSourceItems = $this->sourceItemRepository->getList($searchCriteriaProduct)->getItems();
            $duplicateSourceItems = $this->sourceItemRepository->getList($searchCriteriaDuplicate)->getItems();

            $this->assertEquals(
                count($productSourceItems),
                count($duplicateSourceItems)
            );

            do {
                $productSourceItem = current($productSourceItems);
                $duplicateSourceItem = current($duplicateSourceItems);

                $this->assertEquals(
                    $productSourceItem->getStatus(),
                    $duplicateSourceItem->getStatus()
                );
                $this->assertEquals(
                    $productSourceItem->getQuantity(),
                    $duplicateSourceItem->getQuantity()
                );
            } while (next($productSourceItems) !== false && next($duplicateSourceItems));
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $this->rollbackDuplicate($duplicate);
        }
    }

    /**
     * Rollback duplicated Product and it's Source Items.
     *
     * @param ProductInterface $product
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function rollbackDuplicate(ProductInterface $product): void
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            SourceItemInterface::SKU,
            $product->getSku()
        )->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        if (!empty($sourceItems)) {
            $this->sourceItemsDelete->execute($sourceItems);
        }
        $this->productRepository->delete($product);
    }

    /**
     * Data Provider for testDuplicateInSingleSourceMode.
     *
     * @return array
     */
    public function duplicateInSingleSourceModeDataProvider(): array
    {
        return [
            [
                'simple',
            ],
        ];
    }

    /**
     * Data Provider for testDuplicateInMultiSourceMode.
     *
     * @return array
     */
    public function duplicateInMultiSourceModeDataProvider(): array
    {
        return [
            'Multiple Source Items' => [
                'SKU-1',
            ],
            'Single Source Item In Stock' => [
                'SKU-2',
            ],
            'Single Source Item Out Of Stock' => [
                'SKU-3',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->copier = Bootstrap::getObjectManager()
            ->create(Copier::class);
        $this->productRepository = Bootstrap::getObjectManager()
            ->create(ProductRepositoryInterface::class);
        $this->sourceItemRepository = Bootstrap::getObjectManager()
            ->create(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()
            ->create(SearchCriteriaBuilder::class);
        $this->sourceItemsDelete = Bootstrap::getObjectManager()
            ->create(SourceItemsDeleteInterface::class);
    }
}
