<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Indexer\SourceItem;

use Magento\InventoryIndexer\Indexer\SourceItem\GetSalableStatuses;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSalableStatusesCached;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetSalableStatusesCachedTest extends TestCase
{
    /**
     * @var GetSalableStatusesCached
     */
    private $model;

    /**
     * @var GetSalableStatuses|MockObject
     */
    private $getSalableStatuses;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->getSalableStatuses = $this->createMock(GetSalableStatuses::class);
        $this->model = new GetSalableStatusesCached($this->getSalableStatuses);
    }

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $this->getSalableStatuses->expects($this->exactly(2))->method('execute')->willReturn([]);
        $this->model->execute([1,2,3], 'before');
        $this->model->execute([3,2,1], 'before');
        $this->model->execute([3,1,2], 'before');

        $this->model->execute([1,2,3], 'after');
        $this->model->execute([3,2,1], 'after');
        $this->model->execute([3,1,2], 'after');
    }
}
