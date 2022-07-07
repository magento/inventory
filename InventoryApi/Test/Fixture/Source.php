<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Test\Fixture;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class Source implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        SourceInterface::SOURCE_CODE => 'source%uniqid%',
        SourceInterface::NAME => 'Source%uniqid%',
        SourceInterface::ENABLED => true,
        SourceInterface::DESCRIPTION => null,
        SourceInterface::LATITUDE => null,
        SourceInterface::LONGITUDE => null,
        SourceInterface::CONTACT_NAME => null,
        SourceInterface::EMAIL => null,
        SourceInterface::STREET => null,
        SourceInterface::CITY => null,
        SourceInterface::REGION_ID => null,
        SourceInterface::REGION => null,
        SourceInterface::POSTCODE => '10001',
        SourceInterface::COUNTRY_ID => 'US',
        SourceInterface::PHONE => null,
        SourceInterface::FAX => null,
        SourceInterface::USE_DEFAULT_CARRIER_CONFIG => 1,
        SourceInterface::CARRIER_LINKS => null,
        SourceInterface::EXTENSION_ATTRIBUTES_KEY => [],
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
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @param ServiceFactory $serviceFactory
     * @param ProcessorInterface $dataProcessor
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        ProcessorInterface $dataProcessor,
        ResourceConnection $resourceConnection
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->dataProcessor = $dataProcessor;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters. Same format as Source::DEFAULT_DATA.
     */
    public function apply(array $data = []): ?DataObject
    {
        $data = $this->prepareData($data);
        $saveService = $this->serviceFactory->create(SourceRepositoryInterface::class, 'save');
        $saveService->execute(['source' => $data]);
        $getService = $this->serviceFactory->create(SourceRepositoryInterface::class, 'get');
        return $getService->execute(['sourceCode' => $data['source_code']]);
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = 'inventory_source';
        $connection->delete(
            $connection->getTableName($tableName),
            [
                SourceInterface::SOURCE_CODE . ' = ?' => $data->getSourceCode(),
            ]
        );
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
