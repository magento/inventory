<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Framework\Validation\ValidationException;
use Magento\CatalogInventory\Model\Indexer\Stock as LegacyIndexer;
use Magento\InventoryCatalog\Model\ResourceModel\TransferInventoryPartially;
use Magento\InventoryCatalogApi\Api\BulkPartialInventoryTransferInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Api\Data\PartialInventoryTransferItemInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\PartialInventoryTransferValidatorInterface;
use Magento\InventoryIndexer\Indexer\IndexScheduler;

class BulkPartialInventoryTransfer implements BulkPartialInventoryTransferInterface
{
    /** @var PartialInventoryTransferValidatorInterface  */
    private $transferValidator;

    /** @var TransferInventoryPartially  */
    private $transferCommand;

    /** @var GetProductIdsBySkusInterface  */
    private $productIdsBySkus;

    /** @var DefaultSourceProviderInterface  */
    private $defaultSourceProvider;

    /** @var IndexScheduler  */
    private $indexScheduler;

    /** @var LegacyIndexer  */
    private $legacyIndexer;

    /**
     * @param PartialInventoryTransferValidatorInterface $partialInventoryTransferValidator
     * @param TransferInventoryPartially $transferInventoryPartiallyCommand
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param IndexScheduler $indexScheduler
     * @param LegacyIndexer $legacyIndexer
     */
    public function __construct(
        PartialInventoryTransferValidatorInterface $partialInventoryTransferValidator,
        TransferInventoryPartially $transferInventoryPartiallyCommand,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        DefaultSourceProviderInterface $defaultSourceProvider,
        IndexScheduler $indexScheduler,
        LegacyIndexer $legacyIndexer
    ) {
        $this->transferValidator = $partialInventoryTransferValidator;
        $this->transferCommand = $transferInventoryPartiallyCommand;
        $this->productIdsBySkus = $getProductIdsBySkus;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->indexScheduler = $indexScheduler;
        $this->legacyIndexer = $legacyIndexer;
    }

    /**
     * Run bulk partial inventory transfer for specified items.
     *
     * @param string $originSourceCode
     * @param string $destinationSourceCode
     * @param PartialInventoryTransferItemInterface[] $items
     * @return void
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function execute(string $originSourceCode, string $destinationSourceCode, array $items): void
    {
        $validationResult = $this->transferValidator->validate($originSourceCode, $destinationSourceCode, $items);
        if (!$validationResult->isValid()) {
            throw new ValidationException(__("Transfer validation failed"), null, 0, $validationResult);
        }

        $this->processTransfer($originSourceCode, $destinationSourceCode, $items);
    }

    /**
     * Process sources transfer
     *
     * @param string $originSourceCode
     * @param string $destinationSourceCode
     * @param PartialInventoryTransferItemInterface[] $items
     */
    private function processTransfer(string $originSourceCode, string $destinationSourceCode, array $items): void
    {
        $processedSkus = [];
        foreach ($items as $item) {
            $this->transferCommand->execute($item, $originSourceCode, $destinationSourceCode);
            $processedSkus[] = $item->getSku();
        }

        $this->updateIndexes([$originSourceCode, $destinationSourceCode], $processedSkus);
    }

    /**
     * Update sources indexes
     *
     * @param string[] $sources
     * @param string[] $skus
     */
    private function updateIndexes(array $sources, array $skus)
    {
        $sources = array_unique($sources);
        $this->indexScheduler->scheduleSources($sources);

        if (in_array($this->defaultSourceProvider->getCode(), $sources)) {
            $this->updateLegacyIndex($skus);
        }
    }

    /**
     * Update legacy index by SKUs
     *
     * @param string[] $skus
     */
    private function updateLegacyIndex(array $skus)
    {
        $productIds = $this->productIdsBySkus->execute($skus);
        $this->legacyIndexer->executeList($productIds);
    }
}
