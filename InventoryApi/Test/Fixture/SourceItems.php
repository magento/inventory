<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class SourceItems implements RevertibleDataFixtureInterface
{
    /**
     * @var ServiceFactory
     */
    private ServiceFactory $serviceFactory;

    /**
     * @var ProcessorInterface
     */
    private ProcessorInterface $dataProcessor;

    /**
     * @var DataObjectFactory
     */
    private DataObjectFactory $dataObjectFactory;

    /**
     * @param ServiceFactory $serviceFactory
     * @param ProcessorInterface $dataProcessor
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        ProcessorInterface $dataProcessor,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->dataProcessor = $dataProcessor;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters. Array of data of type SourceItemInterface
     */
    public function apply(array $data = []): ?DataObject
    {
        $service = $this->serviceFactory->create(SourceItemsSaveInterface::class, 'execute');
        $data = $this->prepareData($data);

        $service->execute(['sourceItems' => $data]);

        return $this->dataObjectFactory->create(['data' => ['items' => $data]]);
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $service = $this->serviceFactory->create(SourceItemsDeleteInterface::class, 'execute');
        $service->execute(['sourceItems' => $data['items']]);
    }

    /**
     * Prepare source items data
     *
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $items = [];
        foreach ($data as $item) {
            $items[] = $this->dataProcessor->process($this, $item + SourceItem::DEFAULT_DATA);
        }

        return $items;
    }
}
