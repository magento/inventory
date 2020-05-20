<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\CatalogInventory\Model\Indexer\Stock as LegacyIndexer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryCatalog\Model\ResourceModel\TransferInventoryPartially;
use Magento\InventoryCatalogApi\Api\BulkPartialInventoryTransferInterface;
use Magento\InventoryCatalogApi\Api\Data\PartialInventoryTransferItemInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\PartialInventoryTransferValidatorInterface;
use Magento\InventoryIndexer\Indexer\Source\SourceIndexer;

class BulkPartialInventoryTransfer implements BulkPartialInventoryTransferInterface
{
    /**
     * @var PartialInventoryTransferValidatorInterface
     */
    private $transferValidator;

    /**
     * @var TransferInventoryPartially
     */
    private $transferCommand;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $productIdsBySkus;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var SourceIndexer
     */
    private $sourceIndexer;

    /**
     * @var LegacyIndexer
     */
    private $legacyIndexer;

    /**
     * @param PartialInventoryTransferValidatorInterface $partialInventoryTransferValidator
     * @param TransferInventoryPartially $transferInventoryPartiallyCommand
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param SourceIndexer $sourceIndexer
     * @param LegacyIndexer $legacyIndexer
     */
    public function __construct(
        PartialInventoryTransferValidatorInterface $partialInventoryTransferValidator,
        TransferInventoryPartially $transferInventoryPartiallyCommand,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        DefaultSourceProviderInterface $defaultSourceProvider,
        SourceIndexer $sourceIndexer,
        LegacyIndexer $legacyIndexer
    ) {
        $this->transferValidator = $partialInventoryTransferValidator;
        $this->transferCommand = $transferInventoryPartiallyCommand;
        $this->productIdsBySkus = $getProductIdsBySkus;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->sourceIndexer = $sourceIndexer;
        $this->legacyIndexer = $legacyIndexer;
    }

    /**
     * Run bulk partial inventory transfer for specified items.
     *
     * @param string $originSourceCode
     * @param string $destinationSourceCode
     * @param PartialInventoryTransferItemInterface[] $items
     * @return void
     * @throws ValidationException
     * @throws NoSuchEntityException
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
     * Transfer source items.
     *
     * @param string $originSourceCode
     * @param string $destinationSourceCode
     * @param PartialInventoryTransferItemInterface[] $items
     * @throws NoSuchEntityException
     */
    private function processTransfer(string $originSourceCode, string $destinationSourceCode, array $items): void
    {
        $processedSkus = [];
        foreach ($items as $item) {
            $this->transferCommand->execute($item, $originSourceCode, $destinationSourceCode);
            $processedSkus[] = $item->getSku();
        }

        $sources = array_unique([$originSourceCode, $destinationSourceCode]);
        $this->sourceIndexer->executeList($sources);

        if (in_array($this->defaultSourceProvider->getCode(), $sources)) {
            $productIds = $this->productIdsBySkus->execute($processedSkus);
            $this->legacyIndexer->executeList($productIds);
        }
    }
}
