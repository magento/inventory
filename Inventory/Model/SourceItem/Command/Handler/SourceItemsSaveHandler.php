<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Command\Handler;

use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\Inventory\Model\IsProductAssignedToStock\CacheStorage;
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
     * @param SourceItemsValidator $sourceItemsValidator
     * @param SaveMultiple $saveMultiple
     * @param LoggerInterface $logger
     * @param CacheStorage $isProductAssignedToStockCacheStorage
     */
    public function __construct(
        private readonly SourceItemsValidator $sourceItemsValidator,
        private readonly SaveMultiple $saveMultiple,
        private readonly LoggerInterface $logger,
        private readonly CacheStorage $isProductAssignedToStockCacheStorage
    ) {
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
}
