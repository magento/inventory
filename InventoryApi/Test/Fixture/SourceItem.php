<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class SourceItem implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'sku' => 'sku%uniqid%',
        'source_code' => 'source%uniqid%',
        'quantity' => 100,
        'status' => SourceItemInterface::STATUS_IN_STOCK,
    ];

    /**
     * @var ServiceFactory
     */
    private ServiceFactory $serviceFactory;

    /**
     * @var ProcessorInterface
     */
    private ProcessorInterface $dataProcessor;

    /**
     * @param ServiceFactory $serviceFactory
     * @param ProcessorInterface $dataProcessor
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        ProcessorInterface $dataProcessor
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->dataProcessor = $dataProcessor;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters. Same format as SourceItem::DEFAULT_DATA.
     */
    public function apply(array $data = []): ?DataObject
    {
        $service = $this->serviceFactory->create(SourceItemsSaveInterface::class, 'execute');

        return $service->execute(['sourceItems' => [$this->prepareData($data)]]);
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $service = $this->serviceFactory->create(SourceItemsDeleteInterface::class, 'execute');
        $service->execute(['sourceItems' => [$this->prepareData($data->getData())]]);
    }

    /**
     * Prepare source item data
     *
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $data = array_merge(self::DEFAULT_DATA, $data);

        return $this->dataProcessor->process($this, $data);
    }
}
