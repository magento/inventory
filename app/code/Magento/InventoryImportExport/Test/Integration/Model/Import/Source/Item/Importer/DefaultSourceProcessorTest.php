<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Test\Integration\Model\Import\Source\Item\Importer;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalog\Api\DefaultSourceProviderInterface;
use Magento\InventoryImportExport\Model\Import\Source\Item\Importer\DefaultSourceProcessor;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class DefaultSourceProcessorTest extends TestCase
{
    /**
     * @var DefaultSourceProcessor $defaultSourceProcessor
     */
    private $defaultSourceProcessor;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProviderInterface;

    /**
     * Setup Test for Default Source Item Importer Processor
     */
    public function setUp()
    {
        $this->defaultSourceProcessor = Bootstrap::getObjectManager()->get(
            DefaultSourceProcessor::class
        );
        $this->defaultSourceProviderInterface = Bootstrap::getObjectManager()->get(
            DefaultSourceProviderInterface::class
        );
    }

    /**
     * Tests Source Item Import of default source with only a number as value for qty
     *
     * @magentoDbIsolation enabled
     */
    public function testSourceItemProcessorWithNumberValue()
    {
        $stockData = [
            'sku' => 'SKU-1',
            'qty' => 1,
            'is_in_stock' => SourceItemInterface::STATUS_IN_STOCK
        ];

        /** @var SourceItemInterface $importedItem */
        $importedItem = $this->defaultSourceProcessor->execute($stockData);
        $comparableData = $this->buildDataArray($importedItem);

        $this->assertSame($this->getExpectedSourceItemData(), $comparableData);
    }

    /**
     * Tests Source Item Import of default source with a string-number as value for qty eg. default=1
     *
     * @magentoDbIsolation enabled
     */
    public function testSourceItemProcessorWithIdAndNumberValue()
    {
        $stockData = [
            'sku' => 'SKU-1',
            'qty' => '1=1',
            'is_in_stock' => SourceItemInterface::STATUS_IN_STOCK
        ];

        /** @var SourceItemInterface $importedItem */
        $importedItem = $this->defaultSourceProcessor->execute($stockData);
        $comparableData = $this->buildDataArray($importedItem);

        $this->assertSame($this->getExpectedSourceItemData(), $comparableData);
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
            SourceItemInterface::QUANTITY => 1,
            SourceItemInterface::SOURCE_ID => $this->defaultSourceProviderInterface->getId(),
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK
        ];
    }
}
