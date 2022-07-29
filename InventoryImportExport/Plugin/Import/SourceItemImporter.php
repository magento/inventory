<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Plugin\Import;

use Magento\CatalogImportExport\Model\StockItemImporterInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

class SourceItemImporter
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
     * @var SourceItemInterfaceFactory $sourceItemFactory
     */
    private $sourceItemFactory;

    /**
     * Default Source Provider
     *
     * @var DefaultSourceProviderInterface $defaultSource
     */
    private $defaultSource;

    /**
     * Fetch Source Items interface
     *
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * StockItemImporter constructor
     *
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     */
    public function __construct(
        SourceItemsSaveInterface $sourceItemsSave,
        SourceItemInterfaceFactory $sourceItemFactory,
        DefaultSourceProviderInterface $defaultSourceProvider,
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        IsSingleSourceModeInterface $isSingleSourceMode
    ) {
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->defaultSource = $defaultSourceProvider;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->isSingleSourceMode = $isSingleSourceMode;
    }

    /**
     * After plugin Import to import Stock Data to Source Items
     *
     * @param StockItemImporterInterface $subject
     * @param mixed $result
     * @param array $stockData
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     * @return void
     * @see StockItemImporterInterface::import()
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterImport(
        StockItemImporterInterface $subject,
        mixed $result,
        array $stockData
    ) {
        $sourceItems = [];
        foreach ($stockData as $sku => $stockDatum) {
            $inStock = (isset($stockDatum['is_in_stock'])) ? (int)$stockDatum['is_in_stock'] : 0;
            $qty = (isset($stockDatum['qty'])) ? $stockDatum['qty'] : 0;
            /** @var SourceItemInterface $sourceItem */
            $sourceItem = $this->sourceItemFactory->create();
            $sourceItem->setSku((string)$sku);
            $sourceItem->setSourceCode($this->defaultSource->getCode());
            $sourceItem->setQuantity((float)$qty);
            $sourceItem->setStatus($inStock);
            if ($this->isSourceItemAllowed($sourceItem)) {
                $sourceItems[] = $sourceItem;
            }
        }
        if (count($sourceItems) > 0) {
            /** SourceItemInterface[] $sourceItems */
            $this->sourceItemsSave->execute($sourceItems);
        }
    }

    /**
     * In case of multiple sources, if the existing product already has source codes other than `default`,
     * then this check will prevent a new entry for `default` source code with qty = 0.
     *
     * @param SourceItemInterface $sourceItem
     * @return bool
     */
    private function isSourceItemAllowed(SourceItemInterface $sourceItem): bool
    {
        if ($this->isSingleSourceMode->execute()) {
            return true;
        }

        $existingSourceCodes = [];
        $existingSourceItems = $this->getSourceItemsBySku->execute($sourceItem->getSku());
        foreach ($existingSourceItems as $exitingSourceItem) {
            $existingSourceCodes[] = $exitingSourceItem->getSourceCode();
        }

        return !(!$sourceItem->getQuantity()
            && count($existingSourceCodes)
            && !in_array($sourceItem->getSourceCode(), $existingSourceCodes, true));
    }
}
