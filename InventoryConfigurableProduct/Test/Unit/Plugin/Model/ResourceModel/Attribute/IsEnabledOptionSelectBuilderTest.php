<?php
/*************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * ***********************
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Unit\Plugin\Model\ResourceModel\Attribute;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\ScopeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionSelectBuilderInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryConfigurableProduct\Plugin\Model\ResourceModel\Attribute\IsEnabledOptionSelectBuilder;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IsEnabledOptionSelectBuilderTest extends TestCase
{
    /**
     * @var ProductAttributeRepositoryInterface|MockObject
     */
    private ProductAttributeRepositoryInterface $attributeRepository;

    /**
     * @var MetadataPool|MockObject
     */
    private MetadataPool $metadataPool;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->attributeRepository = $this->createMock(ProductAttributeRepositoryInterface::class);
        $this->metadataPool = $this->createMock(MetadataPool::class);
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function testAfterGetSelect(): void
    {
        $subject = $this->createMock(OptionSelectBuilderInterface::class);
        $superAttribute = $this->createMock(AbstractAttribute::class);
        $productId = 1;

        $scope = $this->createMock(ScopeInterface::class);
        $scope->expects($this->once())->method('getId')->willReturn(1);

        $status = $this->createMock(AbstractAttribute::class);
        $status->expects($this->exactly(2))
            ->method('getBackendTable')
            ->willReturn('catalog_product_entity_int');
        $status->expects($this->exactly(2))->method('getAttributeId')->willReturn(95);

        $metadata = $this->createMock(EntityMetadataInterface::class);
        $metadata->expects($this->once())->method('getLinkField')->willReturn('row_id');
        $this->metadataPool->expects($this->once())->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($metadata);
        $this->attributeRepository->expects($this->once())
            ->method('get')
            ->with(ProductInterface::STATUS)
            ->willReturn($status);

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())
            ->method('getIfNullSql')
            ->with('entity_status_store.value', 'entity_status_global.value')
            ->willReturn('IFNULL(entity_status_store.value, entity_status_global.value) = 1');

        $select = $this->createMock(Select::class);
        $select->expects($this->once())->method('getConnection')->willReturn($adapter);
        $select->expects($this->once())->method('joinInner')->with(
            ['entity_status_global' => 'catalog_product_entity_int'],
            "entity_status_global.row_id = entity.row_id"
            . " AND entity_status_global.attribute_id = 95"
            . " AND entity_status_global.store_id = " . Store::DEFAULT_STORE_ID,
            []
        )->willReturnSelf();
        $select->expects($this->once())->method('joinLeft')->with(
            ['entity_status_store' => 'catalog_product_entity_int'],
            "entity_status_store.row_id = entity.row_id"
            . " AND entity_status_store.attribute_id = 95"
            . " AND entity_status_store.store_id = 1",
            []
        )->willReturnSelf();
        $select->expects($this->once())->method('where')->with(
            'IFNULL(entity_status_store.value, entity_status_global.value) = 1 = ?',
            ProductStatus::STATUS_ENABLED
        )->willReturnSelf();

        $plugin = new IsEnabledOptionSelectBuilder($this->attributeRepository, $this->metadataPool);
        $plugin->afterGetSelect($subject, $select, $superAttribute, $productId, $scope);
    }
}
