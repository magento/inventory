<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Plugin\Import;

use Magento\CatalogImportExport\Model\StockItemImporterInterface;
use Magento\CatalogImportExport\Model\Import\Product\SkuProcessor;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * Assigning products to default source
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var SkuProcessor
     */
    private $skuProcessor;

    /**
     * StockItemImporter constructor
     *
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param ResourceConnection $resourceConnection
     * @param SkuProcessor $skuProcessor
     */
    public function __construct(
        SourceItemsSaveInterface $sourceItemsSave,
        SourceItemInterfaceFactory $sourceItemFactory,
        DefaultSourceProviderInterface $defaultSourceProvider,
        IsSingleSourceModeInterface $isSingleSourceMode,
        ResourceConnection $resourceConnection,
        SkuProcessor $skuProcessor
    ) {
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->defaultSource = $defaultSourceProvider;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->resourceConnection = $resourceConnection;
        $this->skuProcessor = $skuProcessor;
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
        $skusWithDefaultSource = $this->getSourceRelation(array_keys($stockData));

        foreach ($stockData as $sku => $stockDatum) {
            $isNewSku = true;
            if (array_key_exists(strtolower((string)$sku), $this->skuProcessor->getOldSkus())) {
                $isNewSku = false;
            }

            $inStock = $stockDatum['is_in_stock'] ?? 0;
            $qty = $stockDatum['qty'] ?? 0;
            $sourceItem = $this->sourceItemFactory->create();
            $sourceItem->setSku((string)$sku);
            $sourceItem->setSourceCode($this->defaultSource->getCode());
            $sourceItem->setQuantity((float)$qty);
            $sourceItem->setStatus((int)$inStock);

            if ($isNewSku
                || $this->isSingleSourceMode->execute()
                || $this->isSourceItemAllowed($sourceItem, $skusWithDefaultSource)) {
                $sourceItems[] = $sourceItem;
            }
        }
        if (count($sourceItems) > 0) {
            /** SourceItemInterface[] $sourceItems */
            $this->sourceItemsSave->execute($sourceItems);
        }
    }

    /**
     * Assignment of default stock for existing products
     *
     * In case of multiple sources, if the existing product already has assigned to source codes other than `default`,
     * then this check will prevent assigning it to `default` source code if qty is set to 0.
     *
     * @param SourceItemInterface $sourceItem
     * @param array $existingSourceCodes
     * @return bool
     */
    private function isSourceItemAllowed(SourceItemInterface $sourceItem, array $existingSourceCodes): bool
    {
        return !(!$sourceItem->getQuantity() && !array_key_exists($sourceItem->getSku(), $existingSourceCodes));
    }

    /**
     * Store product sku and source relations in initialized variable
     *
     * @param array $listSku
     * @return array
     */
    private function getSourceRelation(array $listSku): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            $this->resourceConnection->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM),
            [SourceItemInterface::SKU, SourceItemInterface::SOURCE_CODE]
        )->where(
            SourceItemInterface::SKU . ' IN (?)',
            $listSku
        )->where(
            SourceItemInterface::SOURCE_CODE . ' = ?',
            $this->defaultSource->getCode()
        );
        return $connection->fetchPairs($select);
    }
}
