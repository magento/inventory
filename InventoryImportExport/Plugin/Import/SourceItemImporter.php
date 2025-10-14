<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Plugin\Import;

use Magento\CatalogImportExport\Model\Import\Product\SkuStorage;
use Magento\CatalogImportExport\Model\StockItemProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\Inventory\Model\ResourceModel\SourceItem as SourceItemResourceModel;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;

/**
 * Assigning products to default source
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SourceItemImporter
{
    /**
     * These inventory configurations affects all sources
     *
     * @var string[]
     */
    private const STOCK_CONFIGURATION_FIELDS = [
        'min_qty' => null,
        'use_config_min_qty' => null,
        'backorders' => null,
        'use_config_backorders' => null,
        'out_of_stock_qty' => null, // alias for min_qty
        'allow_backorders' => null // alias for backorders
    ];

    /**
     * StockItemImporter constructor
     *
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param SkuStorage $skuStorage
     * @param SourceItemResourceModel $sourceItemResourceModel
     * @param SourceItemIndexer $sourceItemIndexer
     */
    public function __construct(
        private readonly SourceItemsSaveInterface $sourceItemsSave,
        private readonly SourceItemInterfaceFactory $sourceItemFactory,
        private readonly DefaultSourceProviderInterface $defaultSourceProvider,
        private readonly IsSingleSourceModeInterface $isSingleSourceMode,
        private readonly SkuStorage $skuStorage,
        private readonly SourceItemResourceModel $sourceItemResourceModel,
        private readonly SourceItemIndexer $sourceItemIndexer
    ) {
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
        $isSingleSourceMode = $this->isSingleSourceMode->execute();
        // No need to load existing source items in single source mode as we know the only source is 'default'
        $existingSourceItemsBySKU = $isSingleSourceMode ? [] : $this->getSourceItems(array_keys($stockData));
        $defaultSourceCode = $this->defaultSourceProvider->getCode();
        $sourceItemIds = [];
        foreach ($stockData as $sku => $stockDatum) {
            $sku = (string)$sku;
            $sources = $existingSourceItemsBySKU[$sku] ?? [];
            $isQtyExplicitlySet = (bool) ($importedData[$sku]['qty'] ?? false);
            $hasDefaultSource = isset($sources[$defaultSourceCode]);

            if ($this->shouldUpdateDefaultSourceItem($sku, $isQtyExplicitlySet, $hasDefaultSource, $isSingleSourceMode)
            ) {
                $sourceItem = $this->sourceItemFactory->create();
                $sourceItem->setSku($sku);
                $sourceItem->setSourceCode($defaultSourceCode);
                $sourceItem->setQuantity((float) ($stockDatum['qty'] ?? 0));
                $sourceItem->setStatus((int) ($stockDatum['is_in_stock'] ?? 0));
                $sourceItems[] = $sourceItem;
            }

            unset($sources[$defaultSourceCode]);
            // Is there any other source (except the default source) assigned to the product
            if (count($sources) > 0
                && array_filter(
                    array_intersect_key($importedData[$sku] ?? [], self::STOCK_CONFIGURATION_FIELDS),
                    fn ($value) => $value !== null
                )
            ) {
                array_push($sourceItemIds, ...array_values($sources));
            }
        }
        if (count($sourceItems) > 0) {
            $this->sourceItemsSave->execute($sourceItems);
        }
        // Reindex non default source items if global stock configuration such as backorders have changed
        if (!empty($sourceItemIds)) {
            $this->sourceItemIndexer->executeList($sourceItemIds);
        }
    }

    /**
     * Checks whether default source item should be updated for the given SKU
     *
     * Prevent products to be assigned to `default` source unless
     *
     * - The product is new
     * - The qty is explicitly set in the import file
     * - Only one source exists (single source mode)
     * - The product is already assigned to the default source
     *
     * @param string $sku
     * @param bool $hasQty
     * @param bool $hasDefaultSource
     * @param bool $isSingleSourceMode
     * @return bool
     */
    private function shouldUpdateDefaultSourceItem(
        string $sku,
        bool $hasQty,
        bool $hasDefaultSource,
        bool $isSingleSourceMode
    ): bool {
        return !$this->skuStorage->has($sku) || $hasQty || $isSingleSourceMode || $hasDefaultSource;
    }

    /**
     * Fetch product's source items
     *
     * @param array $skus
     * @return array
     */
    private function getSourceItems(array $skus): array
    {
        $fields = [
            SourceItemResourceModel::ID_FIELD_NAME,
            SourceItemInterface::SOURCE_CODE,
            SourceItemInterface::SKU
        ];
        $result = [];
        foreach ($this->sourceItemResourceModel->findAllBySkus($skus, $fields) as $item) {
            $result[$item[SourceItemInterface::SKU]][$item[SourceItemInterface::SOURCE_CODE]] =
                $item[SourceItemResourceModel::ID_FIELD_NAME];
        }
        return $result;
    }
}
