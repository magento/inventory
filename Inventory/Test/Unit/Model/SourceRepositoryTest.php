<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Inventory\Model\Source\Command\GetInterface;
use Magento\Inventory\Model\Source\Command\GetListInterface;
use Magento\Inventory\Model\Source\Command\SaveInterface;
use Magento\Inventory\Model\SourceRepository;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SourceRepositoryTest extends TestCase
{
    /**
     * @var SaveInterface|MockObject
     */
    private $commandSave;

    /**
     * @var GetInterface|MockObject
     */
    private $commandGet;

    /**
     * @var GetListInterface|MockObject
     */
    private $commandGetList;

    /**
     * @var SourceInterface|MockObject
     */
    private $source;

    /**
     * @var SourceSearchResultsInterface|MockObject
     */
    private $searchResult;

    /**
     * @var SourceRepository
     */
    private $sourceRepository;

    protected function setUp(): void
    {
        $this->commandSave = $this->getMockBuilder(SaveInterface::class)
            ->getMock();
        $this->commandGet = $this->getMockBuilder(GetInterface::class)
            ->getMock();
        $this->commandGetList = $this->getMockBuilder(GetListInterface::class)
            ->getMock();
        $this->source = $this->getMockBuilder(SourceInterface::class)
            ->getMock();
        $this->searchResult = $this->getMockBuilder(SourceSearchResultsInterface::class)
            ->getMock();

        $this->sourceRepository = (new ObjectManager($this))->getObject(
            SourceRepository::class,
            [
                'commandSave' => $this->commandSave,
                'commandGet' => $this->commandGet,
                'commandGetList' => $this->commandGetList,
            ]
        );
    }

    public function testSave()
    {
        $sourceCode = 'source-code';

        $this->commandSave
            ->expects($this->once())
            ->method('execute')
            ->with($this->source)
            ->willReturn($sourceCode);

        $this->sourceRepository->save($this->source);
    }

    public function testSaveWithCouldNotSaveException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('Some error');
        $this->commandSave
            ->expects($this->once())
            ->method('execute')
            ->with($this->source)
            ->willThrowException(new CouldNotSaveException(__('Some error')));

        $this->sourceRepository->save($this->source);
    }

    public function testGet()
    {
        $sourceCode = 'source-code';

        $this->commandGet
            ->expects($this->once())
            ->method('execute')
            ->with($sourceCode)
            ->willReturn($this->source);

        self::assertEquals($this->source, $this->sourceRepository->get($sourceCode));
    }

    public function testGetWithNoSuchEntityException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('Some error');
        $sourceCode = 'source-code';

        $this->commandGet
            ->expects($this->once())
            ->method('execute')
            ->with($sourceCode)
            ->willThrowException(new NoSuchEntityException(__('Some error')));

        $this->sourceRepository->get($sourceCode);
    }

    public function testGetListWithoutSearchCriteria()
    {
        $this->commandGetList
            ->expects($this->once())
            ->method('execute')
            ->with(null)
            ->willReturn($this->searchResult);

        self::assertEquals($this->searchResult, $this->sourceRepository->getList());
    }

    public function testGetListWithSearchCriteria()
    {
        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->getMock();

        $this->commandGetList
            ->expects($this->once())
            ->method('execute')
            ->with($searchCriteria)
            ->willReturn($this->searchResult);

        self::assertEquals($this->searchResult, $this->sourceRepository->getList($searchCriteria));
    }
}
