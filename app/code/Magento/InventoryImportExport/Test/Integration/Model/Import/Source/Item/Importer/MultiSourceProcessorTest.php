<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Test\Integration\Model\Import\Source\Item\Importer;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Api\DefaultSourceProviderInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryImportExport\Model\Import\Source\Item\Importer\MultiSourceProcessor;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class MultiSourceProcessorTest extends TestCase
{
    /**
     * @var MultiSourceProcessor $multiSourceProcessor
     */
    private $multiSourceProcessor;

    /**
     * @var DataObjectHelper $dataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var SourceInterfaceFactory $sourceInterfaceFactory
     */
    private $sourceInterfaceFactory;

    /**
     * @var SourceRepositoryInterface $sourceRepositoryInterface
     */
    private $sourceRepositoryInterface;

    /**
     * @var SourceInterface $customSource
     */
    private $customSource;

    /**
     * Array of Valid Data for Import Tests
     *
     * @var array $validStockData
     */
    private $validStockData;

    /**
     * Array of Invalid Data for Import Tests
     *
     * @var array $invalidStockData
     */
    private $invalidStockData;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProviderInterface;

    /**
     * Setup Test for Default Source Item Importer Processor
     *
     * @magentoDbIsolation enabled
     */
    public function setUp()
    {
        $this->multiSourceProcessor = Bootstrap::getObjectManager()->get(
            MultiSourceProcessor::class
        );

        $this->sourceInterfaceFactory = Bootstrap::getObjectManager()->get(
            SourceInterfaceFactory::class
        );

        $this->sourceRepositoryInterface = Bootstrap::getObjectManager()->get(
            SourceRepositoryInterface::class
        );

        $this->dataObjectHelper = Bootstrap::getObjectManager()->get(
            DataObjectHelper::class
        );

        $this->defaultSourceProviderInterface = Bootstrap::getObjectManager()->get(
            DefaultSourceProviderInterface::class
        );

        try {
            // Try loading a Custom Source with SourceID 2
            $this->customSource = $this->sourceRepositoryInterface->get(2);
        } catch (NoSuchEntityException $e) {
            // If we get a NoSuchEntity Exception lets create a new one to use in the tests
            $data = [
                SourceInterface::SOURCE_ID => 2,
                SourceInterface::NAME => 'Custom Source',
                SourceInterface::ENABLED => 1,
                SourceInterface::DESCRIPTION => 'Custom Source',
                SourceInterface::LATITUDE => 0,
                SourceInterface::LONGITUDE => 0,
                SourceInterface::PRIORITY => 0,
                SourceInterface::COUNTRY_ID => 'US',
                SourceInterface::POSTCODE => '00000'
            ];
            $customSource = $this->sourceInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray($customSource, $data, SourceInterface::class);
            // Save it then set it as $customSource on the class so we can use it in the tests
            $this->sourceRepositoryInterface->save($customSource);
            $this->customSource = $this->sourceRepositoryInterface->get(2);
        }

        // Array of stock data to import with valid data
        $this->validStockData = [
            'sku' => 'SKU-1',
            'qty' => '1=10|2=20',
            'is_in_stock' => SourceItemInterface::STATUS_IN_STOCK
        ];

        // Array of stock data to import with invalid source id for custom source
        $this->invalidStockData = [
            'sku' => 'SKU-1',
            'qty' => '1=10|1234567890=20',
            'is_in_stock' => SourceItemInterface::STATUS_IN_STOCK
        ];
    }

    /**
     * Tests Source Item Import of default source with a valid sourceId 2
     *
     * @magentoDbIsolation enabled
     */
    public function testSourceItemProcessorWithValidCustomSourceId()
    {
        /** @var SourceItemInterface $importedItem */
        $importedItems = $this->multiSourceProcessor->execute($this->validStockData, '1');
        $comparableData = $this->buildDataArray($importedItems);

        $this->assertSame($this->getExpectedSourceItemsData(), $comparableData);
    }

    /**
     * Tests Source Item Import of default source with an invalid sourceId 1234567890
     *
     * @magentoDbIsolation enabled
     * @expectedException \Magento\Framework\Validation\ValidationException
     */
    public function testSourceItemProcessorWithInValidCustomSourceId()
    {
        $this->multiSourceProcessor->execute($this->invalidStockData, '2');
    }

    /**
     * @param SourceItemInterface[] $sourceItems
     * @return array
     */
    private function buildDataArray($sourceItems)
    {
        $importedItems = [];
        foreach ($sourceItems as $sourceItem) {
            $importedItems[]  = [
                SourceItemInterface::SKU => $sourceItem->getSku(),
                SourceItemInterface::QUANTITY => $sourceItem->getQuantity(),
                SourceItemInterface::SOURCE_ID => $sourceItem->getSourceId(),
                SourceItemInterface::STATUS => $sourceItem->getStatus()
            ];
        }
        return $importedItems;
    }

    /**
     * Return Array of Expected Data of SourceItemInterface after execute
     *
     * @return array
     */
    private function getExpectedSourceItemsData()
    {
        return [
            [
                SourceItemInterface::SKU => 'SKU-1',
                SourceItemInterface::QUANTITY => '10',
                SourceItemInterface::SOURCE_ID => (string)$this->defaultSourceProviderInterface->getId(),
                SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK
            ],
            [
                SourceItemInterface::SKU => 'SKU-1',
                SourceItemInterface::QUANTITY => '20',
                SourceItemInterface::SOURCE_ID => (string)$this->customSource->getSourceId(),
                SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK
            ]
        ];
    }
}
