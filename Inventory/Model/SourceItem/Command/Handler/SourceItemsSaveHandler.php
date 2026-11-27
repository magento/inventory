<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Command\Handler;

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\Inventory\Model\IsProductAssignedToStock\CacheStorage;
use Magento\Inventory\Model\ResourceModel\GetCanonicalSourceCodes;
use Magento\Inventory\Model\ResourceModel\SourceItem\SaveMultiple;
use Magento\Inventory\Model\SourceItem\Validator\SourceItemsValidator;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Psr\Log\LoggerInterface;

/**
 * Save multiple source items service.
 */
class SourceItemsSaveHandler
{
    /**
     * @var GetCanonicalSourceCodes
     */
    private readonly GetCanonicalSourceCodes $getCanonicalSourceCodes;

    /**
     * @param SourceItemsValidator $sourceItemsValidator
     * @param SaveMultiple $saveMultiple
     * @param LoggerInterface $logger
     * @param CacheStorage $isProductAssignedToStockCacheStorage
     * @param GetCanonicalSourceCodes|null $getCanonicalSourceCodes
     */
    public function __construct(
        private readonly SourceItemsValidator $sourceItemsValidator,
        private readonly SaveMultiple $saveMultiple,
        private readonly LoggerInterface $logger,
        private readonly CacheStorage $isProductAssignedToStockCacheStorage,
        ?GetCanonicalSourceCodes $getCanonicalSourceCodes = null
    ) {
        $this->getCanonicalSourceCodes = $getCanonicalSourceCodes
            ?? ObjectManager::getInstance()->get(GetCanonicalSourceCodes::class);
    }

    /**
     * Save Multiple Source item data
     *
     * @param SourceItemInterface[] $sourceItems
     * @return void
     * @throws InputException
     * @throws ValidationException
     * @throws CouldNotSaveException
     */
    public function execute(array $sourceItems)
    {
        if (empty($sourceItems)) {
            throw new InputException(__('Input data is empty'));
        }

        $this->canonicalizeSourceCodes($sourceItems);

        $validationResult = $this->sourceItemsValidator->validate($sourceItems);
        if (!$validationResult->isValid()) {
            $error = current($validationResult->getErrors());
            throw new ValidationException(__('Validation Failed: ' . $error), null, 0, $validationResult);
        }

        try {
            $this->saveMultiple->execute($sourceItems);
            foreach ($sourceItems as $sourceItem) {
                $this->isProductAssignedToStockCacheStorage->delete((string) $sourceItem->getSku());
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotSaveException(__('Could not save Source Item'), $e);
        }
    }

    /**
     * Replace mismatched-case source codes with the canonical code stored in inventory_source.
     *
     * @param SourceItemInterface[] $sourceItems
     * @return void
     */
    private function canonicalizeSourceCodes(array $sourceItems): void
    {
        $sourceCodes = [];
        foreach ($sourceItems as $sourceItem) {
            $sourceCodes[] = $sourceItem->getSourceCode();
        }

        $canonicalSourceCodes = $this->getCanonicalSourceCodes->execute(array_unique($sourceCodes));
        if (!$canonicalSourceCodes) {
            return;
        }

        foreach ($sourceItems as $sourceItem) {
            $canonicalSourceCode = $canonicalSourceCodes[mb_strtolower((string) $sourceItem->getSourceCode())]
                ?? null;
            if ($canonicalSourceCode !== null) {
                $sourceItem->setSourceCode($canonicalSourceCode);
            }
        }
    }
}
