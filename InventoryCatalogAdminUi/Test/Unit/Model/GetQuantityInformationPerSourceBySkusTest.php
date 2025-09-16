<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemSearchResultsInterface;
use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryCatalogAdminUi\Model\GetQuantityInformationPerSourceBySkus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetQuantityInformationPerSourceBySkusTest extends TestCase
{
    /**
     * @var SourceItemRepositoryInterface|MockObject
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilderFactory|MockObject
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var SourceRepositoryInterface|MockObject
     */
    private $sourceRepository;

    /**
     * @var GetQuantityInformationPerSourceBySkus
     */
    private $model;

    /**
     * @var array[]
     */
    private array $sources = [
        'source_code1' => [
            'getSourceCode' => 'source_code1',
            'getName' => 'source_name1',
        ],
        'source_code2' => [
            'getSourceCode' => 'source_code2',
            'getName' => 'source_name2',
        ],
    ];

    /**
     * @var array[]
     */
    private array $sourceItems = [
        [
            'getSku' => 'sku1',
            'getSourceCode' => 'source_code1',
            'getQuantity' => 100.00,
            'getStatus' => 1,
        ],
        [
            'getSku' => 'sku2',
            'getSourceCode' => 'source_code1',
            'getQuantity' => 200.00,
            'getStatus' => 1,
        ],
        [
            'getSku' => 'sku2',
            'getSourceCode' => 'source_code2',
            'getQuantity' => 201.00,
            'getStatus' => 1,
        ],
        [
            'getSku' => 'sku3',
            'getSourceCode' => 'source_code2',
            'getQuantity' => 300.00,
            'getStatus' => 1,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->sourceItemRepository = $this->createMock(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilderFactory = $this->createMock(SearchCriteriaBuilderFactory::class);
        $this->sourceRepository = $this->createMock(SourceRepositoryInterface::class);
        $this->model = new GetQuantityInformationPerSourceBySkus(
            $this->sourceItemRepository,
            $this->searchCriteriaBuilderFactory,
            $this->sourceRepository
        );
    }

    public function testExecute(): void
    {
        $skus = ['sku1', 'sku2', 'sku3'];
        $sourceItems[] = $this->createConfiguredMock(SourceItemInterface::class, $this->sourceItems[0]);
        $sourceItems[] = $this->createConfiguredMock(SourceItemInterface::class, $this->sourceItems[1]);
        $sourceItems[] = $this->createConfiguredMock(SourceItemInterface::class, $this->sourceItems[2]);
        $sourceItems[] = $this->createConfiguredMock(SourceItemInterface::class, $this->sourceItems[3]);
        $sources[] = $this->createConfiguredMock(SourceInterface::class, $this->sources['source_code1']);
        $sources[] = $this->createConfiguredMock(SourceInterface::class, $this->sources['source_code2']);
        $searchCriteria1 = $this->createMock(SearchCriteria::class);
        $searchCriteria2 = $this->createMock(SearchCriteria::class);
        $searchCriteriaBuilder1 = $this->createMock(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder2 = $this->createMock(SearchCriteriaBuilder::class);
        $searchCriteriaBuilders = [$searchCriteriaBuilder1, $searchCriteriaBuilder2];
        $sourceItemSearchResult = $this->createMock(SourceItemSearchResultsInterface::class);
        $sourceSearchResult = $this->createMock(SourceSearchResultsInterface::class);
        $this->searchCriteriaBuilderFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnCallback(
                function () use (&$searchCriteriaBuilders) {
                    return array_shift($searchCriteriaBuilders);
                }
            );

        $searchCriteriaBuilder1->expects($this->once())
            ->method('addFilter')
            ->with(SourceItemInterface::SKU, $skus, 'in')
            ->willReturnSelf();
        $searchCriteriaBuilder1
            ->expects($this->once())->method('create')
            ->willReturn($searchCriteria1);

        $searchCriteriaBuilder2->expects($this->once())
            ->method('addFilter')
            ->with(SourceInterface::SOURCE_CODE, ['source_code1', 'source_code2'], 'in')
            ->willReturnSelf();
        $searchCriteriaBuilder2->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria2);

        $sourceItemSearchResult->expects($this->once())
            ->method('getItems')
            ->willReturn($sourceItems);
        $this->sourceItemRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria1)
            ->willReturn($sourceItemSearchResult);

        $sourceSearchResult->expects($this->once())
            ->method('getItems')
            ->willReturn($sources);
        $this->sourceRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria2)
            ->willReturn($sourceSearchResult);

        $this->assertEquals($this->getExpectedResult($skus), $this->model->execute($skus));
    }

    private function getExpectedResult(array $skus): array
    {
        $result = [];
        foreach ($this->sourceItems as $sourceItem) {
            if (in_array($sourceItem['getSku'], $skus, true)) {
                $result[$sourceItem['getSku']][] = [
                    'source_code' => $sourceItem['getSourceCode'],
                    'quantity' => $sourceItem['getQuantity'],
                    'source_name' => $this->sources[$sourceItem['getSourceCode']]['getName'],
                    'status' => $sourceItem['getStatus'],
                ];
            }
        }
        return $result;
    }
}
