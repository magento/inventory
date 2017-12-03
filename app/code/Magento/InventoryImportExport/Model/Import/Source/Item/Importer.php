<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Import\Source\Item;

use Magento\CatalogImportExport\Model\Import\Source\Item\ImporterInterface;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\Inventory\Model\SourceItemFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalog\Api\DefaultSourceProviderInterface;
use Magento\InventoryImportExport\Model\Import\Source\Item\Importer\CustomSourceProcessor;
use Magento\InventoryImportExport\Model\Import\Source\Item\Importer\DefaultSourceProcessor;
use Magento\InventoryImportExport\Model\Import\Source\Item\Importer\MultiSourceProcessor;

class Importer implements ImporterInterface
{
    /**
     * Source Items Save Interface for saving multiple source items
     *
     * @var SourceItemsSaveInterface $sourceItemsSave
     */
    private $sourceItemsSave;

    /**
     * Source Item Interface Factory
     *
     * @var SourceItemFactory $sourceItemFactory
     */
    private $sourceItemFactory;

    /**
     * Default Source Provider
     *
     * @var DefaultSourceProviderInterface $defaultSource
     */
    private $defaultSource;

    /**
     * Custom Source Processor
     *
     * @var CustomSourceProcessor $customSourceProcessor
     */
    private $customSourceProcessor;

    /**
     * Default Source Processor
     *
     * @var DefaultSourceProcessor $defaultSourceProcessor
     */
    private $defaultSourceProcessor;

    /**
     * Mult Source Processor
     *
     * @var MultiSourceProcessor $multiSourceProcessor
     */
    private $multiSourceProcessor;

    /**
     * Array of Source Items to import
     *
     * @var array $sourceItemsToImport
     */
    private $sourceItemsToImport;

    /**
     * StockItemImporter constructor
     *
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SourceItemFactory $sourceItemFactory
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param CustomSourceProcessor $customSourceProcessor
     * @param DefaultSourceProcessor $defaultSourceProcessor
     * @param MultiSourceProcessor $multiSourceProcessor
     */
    public function __construct(
        SourceItemsSaveInterface $sourceItemsSave,
        SourceItemFactory $sourceItemFactory,
        DefaultSourceProviderInterface $defaultSourceProvider,
        CustomSourceProcessor $customSourceProcessor,
        DefaultSourceProcessor $defaultSourceProcessor,
        MultiSourceProcessor $multiSourceProcessor
    ) {
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->defaultSource = $defaultSourceProvider;
        $this->customSourceProcessor = $customSourceProcessor;
        $this->defaultSourceProcessor = $defaultSourceProcessor;
        $this->multiSourceProcessor = $multiSourceProcessor;
        $this->sourceItemsToImport = [];
    }

    /**
     * Handle Import of Stock Item Data
     *
     * @param array $stockData
     * @return void
     */
    public function import(array $stockData)
    {
        foreach ($stockData as $rowNumber => $stockDatum) {
            if ($this->_isImportDataValid($stockDatum)) {
                if ($sourceItem = $this->processSourceItem($stockDatum, $rowNumber)) {
                    if (is_array($sourceItem)) {
                        foreach ($sourceItem as $item) {
                            /** @var SourceItemInterface $item */
                            $this->_addItemToImport($item);
                        }
                    }else {
                        /** @var SourceItemInterface $sourceItem */
                        $this->_addItemToImport($sourceItem);
                    }
                }
            }
        }
        if (count($this->sourceItemsToImport) > 0) {
            /** Magento\Inventory\Model\SourceItem[] $sourceItemsToImport */
            $this->sourceItemsSave->execute($this->sourceItemsToImport);
        }
    }

    /**
     * Process Source Item Import for either default source only or multi sources
     *
     * @param $stockDatum
     * @param $rowNumber
     * @return \Magento\InventoryApi\Api\Data\SourceItemInterface|bool
     */
    public function processSourceItem($stockDatum, $rowNumber)
    {
        if ($this->isDefaultOnly($stockDatum['qty'])) {
            return $this->defaultSourceProcessor->execute($stockDatum);
        } elseif ($this->isCustomOnly($stockDatum['qty'])) {
            return $this->customSourceProcessor->execute($stockDatum, $rowNumber);
        }
        return $this->multiSourceProcessor->execute($stockDatum, $rowNumber);
    }

    /**
     * Work out whether we only have to insert/update the default source
     *
     * @param $qty
     * @return bool
     */
    private function isDefaultOnly($qty)
    {
        $flag = false;
        // If value is numeric we only have one value and therefore only have to update default source
        if (is_numeric($qty)) {
            $flag = true;
        }
        if (!$flag) {
            // If value contains default and has no pipes we have a singular default value to insert/update
            if (strpos($qty, 'default') !== false &&
                strpos($qty, '|') === false) {
                $flag = true;
            }
        }
        return $flag;
    }

    /**
     * Work out whether we only have to insert/update one custom source
     *
     * @param $qty
     * @return bool
     */
    private function isCustomOnly($qty)
    {
        $flag = false;
        // If value does not contain a pipe (singular entry)
        if (strpos($qty, '|') === false) {
            // Split value at '=' in to key => value
            $parts = explode('=', $qty);
            // Make sure that the first part of our array is set
            if (isset($parts[0])) {
                // If first part is not equal to default, we've got a single custom source to insert/update
                $flag = ($parts[0] != 'default') ? true : false;
            }
        }
        return $flag;
    }

    /**
     * Check import array has some required data before we attempt to import it
     *
     * @param array $data
     * @return bool
     */
    private function _isImportDataValid($data)
    {
        return isset($data[Product::COL_SKU]) && isset($data['qty']);
    }

    /**
     * Add Item to Source Items array for import
     *
     * @param SourceItemInterface $item
     */
    private function _addItemToImport($item)
    {
        $this->sourceItemsToImport[] = $item;
    }
}
