<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\InventoryApi\Api\StockSourceLinksDeleteInterface;
use Magento\InventoryApi\Api\StockSourceLinksSaveInterface;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class StockSourceLinks implements RevertibleDataFixtureInterface
{
    /**
     * @var ServiceFactory
     */
    private ServiceFactory $serviceFactory;

    /**
     * @var DataObjectFactory
     */
    private DataObjectFactory $dataObjectFactory;

    /**
     * @param ServiceFactory $serviceFactory
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters. Array of data of type StockSourceLinkInterface
     */
    public function apply(array $data = []): ?DataObject
    {
        $service = $this->serviceFactory->create(StockSourceLinksSaveInterface::class, 'execute');
        $data = $this->prepareData($data);

        $service->execute(['links' => $data]);

        return $this->dataObjectFactory->create(['data' => ['items' => $data]]);
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $service = $this->serviceFactory->create(StockSourceLinksDeleteInterface::class, 'execute');
        $service->execute(['links' => $data['items']]);
    }

    /**
     * Prepare source item data
     *
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $links = [];
        $priority = 1;
        foreach ($data as $link) {
            $links[] = $link + ['priority' => $priority++];
        }

        return $links;
    }
}
