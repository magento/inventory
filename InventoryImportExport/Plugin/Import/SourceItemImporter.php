<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Plugin\Import;

use Magento\CatalogImportExport\Model\StockItemImporterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

class SourceItemImporter
{
    /**
     * @var array
     */
    private $productSources = [];

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
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * StockItemImporter constructor
     *
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        SourceItemsSaveInterface $sourceItemsSave,
        SourceItemInterfaceFactory $sourceItemFactory,
        DefaultSourceProviderInterface $defaultSourceProvider,
        IsSingleSourceModeInterface $isSingleSourceMode,
        ResourceConnection $resourceConnection
    ) {
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->defaultSource = $defaultSourceProvider;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * After plugin Import to import Stock Data to Source Items
     *
     * @param StockItemImporterInterface $subject
     * @param mixed $result
     * @param array $stockData
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     * @return void
     * @see StockItemImporterInterface::import()
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterImport(
        StockItemImporterInterface $subject,
        mixed $result,
        array $stockData
    ): void {
        $sourceItems = [];
        $this->saveSourceRelation(array_keys($stockData));

        foreach ($stockData as $sku => $stockDatum) {
            $isQtySeExplicitly = ($stockDatum['explicit_qty']) ?? false;
            $inStock = $stockDatum['is_in_stock'] ?? 0;
            $qty = $stockDatum['qty'] ?? 0;
            $sourceItem = $this->sourceItemFactory->create();
            $sourceItem->setSku((string)$sku);
            $sourceItem->setSourceCode($this->defaultSource->getCode());
            $sourceItem->setQuantity((float)$qty);
            $sourceItem->setStatus((int)$inStock);

            if ($isQtySeExplicitly
                || $this->isSingleSourceMode->execute()
                || $this->isSourceItemAllowed($sourceItem)) {
                $sourceItems[] = $sourceItem;
            }
        }
        if (count($sourceItems) > 0) {
            /** SourceItemInterface[] $sourceItems */
            $this->sourceItemsSave->execute($sourceItems);
        }
        $this->clearSourceRelation();
    }

    /**
     * Assignment of default stock for existing products
     *
     * In case of multiple sources, if the existing product already has source codes other than `default`, then this
     * check will prevent a new entry for `default` source code with qty = 0.
     *
     * @param SourceItemInterface $sourceItem
     * @return bool
     */
    private function isSourceItemAllowed(SourceItemInterface $sourceItem): bool
    {
        $existingSourceCodes = $this->getSourceRelation($sourceItem->getSku());

        return !(!$sourceItem->getQuantity()
            && count($existingSourceCodes)
            && !in_array($sourceItem->getSourceCode(), $existingSourceCodes, true));
    }

    /**
     * Store product sku and source relations in initialized variable
     *
     * @param array $listSku
     * @return void
     */
    private function saveSourceRelation(array $listSku): void
    {
        $select = $this->resourceConnection->getConnection()->select()->from(
            $this->resourceConnection->getTableName('inventory_source_item'),
            ['sku', 'source_code']
        )->where('sku IN (?)', $listSku);

        $result = $this->resourceConnection->getConnection()->fetchAll($select);
        foreach ($result as $sourceItem) {
            $this->productSources[$sourceItem['sku']][] = $sourceItem['source_code'];
        }
    }

    /**
     * Clean the relation
     *
     * @return void
     */
    private function clearSourceRelation(): void
    {
        $this->productSources = [];
    }

    /**
     * Get source relation
     *
     * @param string $sku
     * @return array
     */
    private function getSourceRelation(string $sku): array
    {
        return $this->productSources[$sku] ?? [];
    }
}
