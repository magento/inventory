<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Plugin\Import;

use Magento\CatalogImportExport\Model\Import\Product\SkuProcessor;
use Magento\CatalogImportExport\Model\Import\Product\SkuStorage;
use Magento\CatalogImportExport\Model\StockItemProcessorInterface;
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
     * @var SkuStorage
     */
    private SkuStorage $skuStorage;

    /**
     * StockItemImporter constructor
     *
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param ResourceConnection $resourceConnection
     * @param SkuProcessor $skuProcessor
     * @param SkuStorage $skuStorage
     */
    public function __construct(
        SourceItemsSaveInterface $sourceItemsSave,
        SourceItemInterfaceFactory $sourceItemFactory,
        DefaultSourceProviderInterface $defaultSourceProvider,
        IsSingleSourceModeInterface $isSingleSourceMode,
        ResourceConnection $resourceConnection,
        SkuProcessor $skuProcessor,
        SkuStorage $skuStorage
    ) {
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->defaultSource = $defaultSourceProvider;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->resourceConnection = $resourceConnection;
        $this->skuProcessor = $skuProcessor;
        $this->skuStorage = $skuStorage;
    }

    /**
     * After plugin Import to import Stock Data to Source Items
     *
     * @param StockItemProcessorInterface $subject
     * @param mixed $result
     * @param array $stockData
     * @param array $importedData
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(
        StockItemProcessorInterface $subject,
        mixed $result,
        array $stockData,
        array $importedData
    ): void {
        $sourceItems = [];
        $skusHavingDefaultSource = $this->getSkusHavingDefaultSource(array_keys($stockData));

        foreach ($stockData as $sku => $stockDatum) {
            $isNewSku = !$this->skuStorage->has((string)$sku);
            $isQtyExplicitlySet = $importedData[$sku]['qty'] ?? false;

            $inStock = $stockDatum['is_in_stock'] ?? 0;
            $qty = $stockDatum['qty'] ?? 0;
            $sourceItem = $this->sourceItemFactory->create();
            $sourceItem->setSku((string)$sku);
            $sourceItem->setSourceCode($this->defaultSource->getCode());
            $sourceItem->setQuantity((float)$qty);
            $sourceItem->setStatus((int)$inStock);

            //Prevent existing products to be assigned to `default` source, when `qty` is not explicitly set.
            if ($isNewSku
                || $isQtyExplicitlySet
                || $this->isSingleSourceMode->execute()
                || in_array($sourceItem->getSku(), $skusHavingDefaultSource, true)) {
                $sourceItems[] = $sourceItem;
            }
        }
        if (count($sourceItems) > 0) {
            /** SourceItemInterface[] $sourceItems */
            $this->sourceItemsSave->execute($sourceItems);
        }
    }

    /**
     * Fetch product's skus having assigned to `default` source.
     *
     * @param array $listSku
     * @return array
     */
    private function getSkusHavingDefaultSource(array $listSku): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            $this->resourceConnection->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM),
            [SourceItemInterface::SKU]
        )->where(
            SourceItemInterface::SKU . ' IN (?)',
            $listSku
        )->where(
            SourceItemInterface::SOURCE_CODE . ' = ?',
            $this->defaultSource->getCode()
        );

        return $connection->fetchCol($select);
    }
}
