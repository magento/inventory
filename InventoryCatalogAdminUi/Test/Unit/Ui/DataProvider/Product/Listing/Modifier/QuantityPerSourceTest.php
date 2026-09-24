<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Test\Unit\Ui\DataProvider\Product\Listing\Modifier;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryCatalogAdminUi\Ui\DataProvider\Product\Listing\Modifier\QuantityPerSource;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Model\GetAllowedProductTypesForSourceItemManagementInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class QuantityPerSourceTest extends TestCase
{
    /**
     * @var IsSingleSourceModeInterface|Stub
     */
    private $isSingleSourceMode;

    /**
     * @var SourceRepositoryInterface|MockObject
     */
    private $sourceRepository;

    /**
     * @var GetSourceItemsBySkuInterface|MockObject
     */
    private $getSourceItemsBySku;

    /**
     * @var SearchCriteriaBuilder|Stub
     */
    private $searchCriteriaBuilder;

    /**
     * @var GetAllowedProductTypesForSourceItemManagementInterface|Stub
     */
    private $getAllowedProductTypesForSourceItemManagement;

    /**
     * @var QuantityPerSource
     */
    private $modifier;

    protected function setUp(): void
    {
        ObjectManager::setInstance($this->createStub(ObjectManagerInterface::class));

        $this->isSingleSourceMode = $this->createStub(IsSingleSourceModeInterface::class);
        $this->isSingleSourceMode->method('execute')->willReturn(false);

        $this->sourceRepository = $this->createMock(SourceRepositoryInterface::class);
        $this->getSourceItemsBySku = $this->createMock(GetSourceItemsBySkuInterface::class);
        $this->searchCriteriaBuilder = $this->createStub(SearchCriteriaBuilder::class);
        $this->getAllowedProductTypesForSourceItemManagement = $this->createStub(
            GetAllowedProductTypesForSourceItemManagementInterface::class
        );
        $this->getAllowedProductTypesForSourceItemManagement->method('execute')->willReturn(['simple']);

        $this->modifier = new QuantityPerSource(
            $this->isSingleSourceMode,
            null,
            $this->sourceRepository,
            $this->getSourceItemsBySku,
            $this->searchCriteriaBuilder,
            $this->createStub(SourceItemRepositoryInterface::class),
            $this->getAllowedProductTypesForSourceItemManagement
        );
    }

    public function testModifyDataMatchesStoredSourceCodeCaseInsensitively(): void
    {
        $sourceItem = $this->createStub(SourceItemInterface::class);
        $sourceItem->method('getSourceCode')->willReturn('Warehouse_Bravo');
        $sourceItem->method('getQuantity')->willReturn(5.0);

        $source = $this->createStub(SourceInterface::class);
        $source->method('getSourceCode')->willReturn('warehouse_bravo');
        $source->method('getName')->willReturn('Warehouse Bravo');

        $this->getSourceItemsBySku->expects(self::once())
            ->method('execute')
            ->with('sku-1')
            ->willReturn([$sourceItem]);
        $this->stubSourceRepositoryList([$source]);

        $data = $this->modifier->modifyData($this->buildGridData());

        self::assertSame(
            [
                'source_name' => 'Warehouse Bravo',
                'source_code' => 'Warehouse_Bravo',
                'qty' => 5.0,
            ],
            $data['items'][0]['quantity_per_source'][0]
        );
    }

    public function testModifyDataSkipsSourceItemWithNoMatchingSource(): void
    {
        $sourceItem = $this->createStub(SourceItemInterface::class);
        $sourceItem->method('getSourceCode')->willReturn('unknown_source');
        $sourceItem->method('getQuantity')->willReturn(5.0);

        $this->getSourceItemsBySku->expects(self::once())
            ->method('execute')
            ->with('sku-1')
            ->willReturn([$sourceItem]);
        $this->stubSourceRepositoryList([]);

        $data = $this->modifier->modifyData($this->buildGridData());

        self::assertSame([], $data['items'][0]['quantity_per_source']);
    }

    /**
     * @param SourceInterface[] $sources
     * @return void
     */
    private function stubSourceRepositoryList(array $sources): void
    {
        $searchCriteria = $this->createStub(SearchCriteria::class);
        $this->searchCriteriaBuilder->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($searchCriteria);

        $searchResults = $this->createStub(SourceSearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn($sources);
        $this->sourceRepository->expects(self::once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);
    }

    /**
     * @return array
     */
    private function buildGridData(): array
    {
        return [
            'totalRecords' => 1,
            'items' => [
                [
                    'sku' => 'sku-1',
                    'type_id' => 'simple',
                ],
            ],
        ];
    }
}
