<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Test\Integration\Model\Import\Source\Item\Importer;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryImportExport\Model\Import\Source\Item\Importer\CustomSourceProcessor;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class CustomSourceProcessorTest extends TestCase
{
    /**
     * @var CustomSourceProcessor $customSourceProcessor
     */
    private $customSourceProcessor;

    /**
     * @var DataObjectHelper $dataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var SourceInterfaceFactory $sourceFactory
     */
    private $sourceFactory;

    /**
     * @var SourceRepositoryInterface $sourceRepository
     */
    private $sourceRepository;

    /**
     * @var SourceInterface $customSource
     */
    private $customSource;

    /**
     * Array of Data for Import Tests
     *
     * @var array $stockData
     */
    private $stockData;

    /**
     * Setup Test for Default Source Item Importer Processor
     *
     * @magentoDbIsolation enabled
     */
    public function setUp()
    {
        $this->customSourceProcessor = Bootstrap::getObjectManager()->get(
            CustomSourceProcessor::class
        );

        $this->sourceFactory = Bootstrap::getObjectManager()->get(
            SourceInterfaceFactory::class
        );

        $this->sourceRepository = Bootstrap::getObjectManager()->get(
            SourceRepositoryInterface::class
        );

        $this->dataObjectHelper = Bootstrap::getObjectManager()->get(
            DataObjectHelper::class
        );

        try {
            // Try loading a Source with SourceID 2
            $this->customSource = $this->sourceRepository->get(2);
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
            $customSource = $this->sourceFactory->create();
            $this->dataObjectHelper->populateWithArray($customSource, $data, SourceInterface::class);
            // Save it then set it as $customSource on the class so we can use it in the tests
            $this->sourceRepository->save($customSource);
            $this->customSource = $this->sourceRepository->get(2);
        }

        $this->stockData = [
            [
                'sku' => 'SKU-1',
                'qty' => '2=1',
                'is_in_stock' => SourceItemInterface::STATUS_IN_STOCK
            ],
            [
                'sku' => 'SKU-1',
                'qty' => '1234567890=1',
                'is_in_stock' => SourceItemInterface::STATUS_IN_STOCK
            ]
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
        $importedItem = $this->customSourceProcessor->execute($this->stockData[0], '1');
        $comparableData = $this->buildDataArray($importedItem);

        $this->assertSame($this->getExpectedSourceItemData(), $comparableData);
    }

    /**
     * Tests Source Item Import of default source with an invalid sourceId 1234567890
     *
     * @magentoDbIsolation enabled
     * @expectedException \Magento\Framework\Validation\ValidationException
     */
    public function testSourceItemProcessorWithInValidCustomSourceId()
    {
        $this->customSourceProcessor->execute($this->stockData[1], '2');
    }

    /**
     * @param SourceItemInterface $sourceItem
     * @return array
     */
    private function buildDataArray($sourceItem)
    {
        return [
            SourceItemInterface::SKU => $sourceItem->getSku(),
            SourceItemInterface::QUANTITY => $sourceItem->getQuantity(),
            SourceItemInterface::SOURCE_ID => $sourceItem->getSourceId(),
            SourceItemInterface::STATUS => $sourceItem->getStatus()
        ];
    }

    /**
     * Return Array of Expected Data of SourceItemInterface after execute
     *
     * @return array
     */
    private function getExpectedSourceItemData()
    {
        return [
            SourceItemInterface::SKU => 'SKU-1',
            SourceItemInterface::QUANTITY => '1',
            SourceItemInterface::SOURCE_ID => $this->customSource->getSourceId(),
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK
        ];
    }
}
