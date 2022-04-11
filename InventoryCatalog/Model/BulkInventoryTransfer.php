<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\CatalogInventory\Model\Indexer\Stock as LegacyIndexer;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryCatalog\Model\ResourceModel\BulkInventoryTransfer as BulkInventoryTransferResource;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockItem;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalogApi\Api\BulkInventoryTransferInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\BulkInventoryTransferValidatorInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryIndexer\Indexer\Source\SourceIndexer;

/**
 * @inheritdoc
 */
class BulkInventoryTransfer implements BulkInventoryTransferInterface
{
    /**
     * @var BulkInventoryTransferValidatorInterface
     */
    private $bulkInventoryTransferValidator;

    /**
     * @var BulkInventoryTransfer
     */
    private $bulkInventoryTransfer;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var LegacyIndexer
     */
    private $legacyIndexer;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var SourceIndexer
     */
    private $sourceIndexer;

    /**
     * @var GetSourceItemsBySkuAndSourceCodes
     */
    private $getSourceItemsBySkuAndSourceCodes;

    /**
     * @var SetDataToLegacyStockStatus
     */
    private $setDataToLegacyStockStatus;

    /**
     * @var SetDataToLegacyStockItem
     */
    private $setDataToLegacyStockItem;

    /**
     * @param BulkInventoryTransferValidatorInterface $inventoryTransferValidator
     * @param BulkInventoryTransferResource $bulkInventoryTransfer
     * @param SourceIndexer $sourceIndexer
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param LegacyIndexer $legacyIndexer
     * @param GetSourceItemsBySkuAndSourceCodes $getSourceItemsBySkuAndSourceCodes
     * @param SetDataToLegacyStockStatus $setDataToLegacyStockStatus
     * @param SetDataToLegacyStockItem $setDataToLegacyStockItem
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        BulkInventoryTransferValidatorInterface $inventoryTransferValidator,
        BulkInventoryTransferResource $bulkInventoryTransfer,
        SourceIndexer $sourceIndexer,
        DefaultSourceProviderInterface $defaultSourceProvider,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        LegacyIndexer $legacyIndexer,
        GetSourceItemsBySkuAndSourceCodes $getSourceItemsBySkuAndSourceCodes,
        SetDataToLegacyStockStatus $setDataToLegacyStockStatus,
        SetDataToLegacyStockItem $setDataToLegacyStockItem
    ) {
        $this->bulkInventoryTransferValidator = $inventoryTransferValidator;
        $this->bulkInventoryTransfer = $bulkInventoryTransfer;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->legacyIndexer = $legacyIndexer;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->sourceIndexer = $sourceIndexer;
        $this->getSourceItemsBySkuAndSourceCodes = $getSourceItemsBySkuAndSourceCodes;
        $this->setDataToLegacyStockStatus = $setDataToLegacyStockStatus;
        $this->setDataToLegacyStockItem = $setDataToLegacyStockItem;
    }

    /**
     * @inheritdoc
     */
    public function execute(
        array $skus,
        string $originSource,
        string $destinationSource,
        bool $unassignFromOrigin
    ): bool {
        $validationResult = $this->bulkInventoryTransferValidator->validate(
            $skus,
            $originSource,
            $destinationSource
        );

        if (!$validationResult->isValid()) {
            throw new ValidationException(
                __('Validation Error: Select different sources for origin and destination.'),
                null,
                0,
                $validationResult
            );
        }

        $this->bulkInventoryTransfer->execute(
            $skus,
            $originSource,
            $destinationSource,
            $unassignFromOrigin
        );

        $this->sourceIndexer->executeList([$originSource, $destinationSource]);

        if (($this->defaultSourceProvider->getCode() === $originSource) ||
            ($this->defaultSourceProvider->getCode() === $destinationSource)) {
            $this->updateStockInfoForLegacyStock($skus);
            $productIds = array_values($this->getProductIdsBySkus->execute($skus));
            $this->reindexLegacy($productIds);
        }

        return true;
    }

    /**
     * Update legacy stock status and stock item
     *
     * @param array $skus
     * @return void
     */
    private function updateStockInfoForLegacyStock(array $skus): void
    {
        foreach ($skus as $sku) {
            $sourceItems = $this->getSourceItemsBySkuAndSourceCodes->execute(
                $sku,
                [$this->defaultSourceProvider->getCode()]
            );
            foreach ($sourceItems as $sourceItem) {
                $this->setDataToLegacyStockItem->execute(
                    (string)$sourceItem->getSku(),
                    (float)$sourceItem->getQuantity(),
                    (int)$sourceItem->getStatus()
                );
                $this->setDataToLegacyStockStatus->execute(
                    (string)$sourceItem->getSku(),
                    (float)$sourceItem->getQuantity(),
                    (int)$sourceItem->getStatus()
                );
            }
        }
    }

    /**
     * Reindex legacy stock (for default source).
     *
     * @param array $productIds
     */
    private function reindexLegacy(array $productIds): void
    {
        $this->legacyIndexer->executeList($productIds);
    }
}
