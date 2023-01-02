<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Command\Handler;

use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple;
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
     * @var SourceItemsValidator
     */
    private $sourceItemsValidator;

    /**
     * @var SaveMultiple
     */
    private $saveMultiple;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DeleteMultiple
     */
    private $deleteMultiple;

    /**
     * @param SourceItemsValidator $sourceItemsValidator
     * @param DeleteMultiple $deleteMultiple
     * @param SaveMultiple $saveMultiple
     * @param LoggerInterface $logger
     */
    public function __construct(
        SourceItemsValidator $sourceItemsValidator,
        DeleteMultiple $deleteMultiple,
        SaveMultiple $saveMultiple,
        LoggerInterface $logger
    ) {
        $this->sourceItemsValidator = $sourceItemsValidator;
        $this->saveMultiple = $saveMultiple;
        $this->logger = $logger;
        $this->deleteMultiple = $deleteMultiple;
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
            $this->deleteMultiple->execute($sourceItems);
            $this->saveMultiple->execute($sourceItems);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotSaveException(__('Could not save Source Item'), $e);
        }
    }
}
