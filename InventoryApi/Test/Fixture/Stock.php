<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class Stock implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        StockInterface::NAME => 'stock%uniqid%',
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
     * @param array $data Parameters. Same format as Stock::DEFAULT_DATA.
     */
    public function apply(array $data = []): ?DataObject
    {
        $saveService = $this->serviceFactory->create(StockRepositoryInterface::class, 'save');

        $stockId = $saveService->execute(['stock' => $this->prepareData($data)]);

        $getService = $this->serviceFactory->create(StockRepositoryInterface::class, 'get');

        return $getService->execute(['stockId' => $stockId]);
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $service = $this->serviceFactory->create(StockRepositoryInterface::class, 'deleteById');
        $service->execute(['stockId' => $data['stock_id']]);
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
