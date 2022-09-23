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
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class DeleteSourceItems implements DataFixtureInterface
{
    /**
     * @var ServiceFactory
     */
    private ServiceFactory $serviceFactory;

    /**
     * @param ServiceFactory $serviceFactory
     */
    public function __construct(
        ServiceFactory $serviceFactory
    ) {
        $this->serviceFactory = $serviceFactory;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters. Array of data of type SourceItemInterface
     */
    public function apply(array $data = []): ?DataObject
    {
        $service = $this->serviceFactory->create(SourceItemsDeleteInterface::class, 'execute');
        $service->execute(['sourceItems' => $data]);

        return null;
    }
}
