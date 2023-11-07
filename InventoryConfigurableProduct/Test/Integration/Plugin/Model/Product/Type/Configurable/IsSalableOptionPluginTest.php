<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Integration\Plugin\Model\Product\Type\Configurable;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ConfigurableView;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Inventory\Model\SourceItem\Command\SourceItemsSave;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IsSalableOptionPluginTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ConfigurableView
     */
    private $block;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceItemsSave
     */
    private $sourceItemSave;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
        $this->serializer = $this->objectManager->get(SerializerInterface::class);
        $this->sourceItemRepository = $this->objectManager->get(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->sourceItemSave = $this->objectManager->get(SourceItemsSave::class);
        $this->layout = $this->objectManager->get(LayoutInterface::class);
    }

    /**
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_product_with_two_child_products.php
     * @return void
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function testGetSalableOptions()
    {
        $product = $this->productRepository->get('Configurable product');
        $this->block = $this->layout->createBlock(ConfigurableView::class);
        $this->block->setProduct($product);
        $config = $this->serializer->unserialize($this->block->getJsonConfig());

        $this->assertEquals(2, count($config['sku']));
        $this->assertContains('Simple option 1', $config['sku']);
        $this->assertContains('Simple option 2', $config['sku']);

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, 'Simple option 1')
            ->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        $sourceItem = reset($sourceItems);
        $sourceItem->setQuantity(0);
        $sourceItem->setStatus(0);
        $this->sourceItemSave->execute([$sourceItem]);

        $this->productRepository->cleanCache();
        $product = $this->productRepository->get('Configurable product');
        $block = $this->layout->createBlock(ConfigurableView::class);
        $block->setProduct($product);
        $config2 = $this->serializer->unserialize($block->getJsonConfig());

        $this->assertEquals(1, count($config2['sku']));
        $this->assertContains('Simple option 2', $config2['sku']);
    }
}
