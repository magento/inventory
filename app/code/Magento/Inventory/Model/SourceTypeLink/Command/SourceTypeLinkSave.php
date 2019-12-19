<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceTypeLink\Command;

use Magento\InventoryApi\Api\SourceTypeLinkSaveInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink\Save;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Validation\ValidationException;
use Magento\Inventory\Model\StockSourceLink\Validator\StockSourceLinksValidator;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SourceTypeLinkSave implements SourceTypeLinkSaveInterface
{
    /**
     * @var Save
     */
    private $save;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StockSourceLinksValidator
     */
    private $stockSourceLinksValidator;

    /**
     * @param StockSourceLinksValidator $stockSourceLinksValidator
     * @param Save $save
     * @param LoggerInterface $logger
     */
    public function __construct(
        StockSourceLinksValidator $stockSourceLinksValidator,
        Save $save,
        LoggerInterface $logger
    ) {
        $this->save = $save;
        $this->logger = $logger;
        $this->stockSourceLinksValidator = $stockSourceLinksValidator;
    }

    /**
     * @inheritDoc
     */
    public function execute(SourceInterface $source): void
    {
        if (empty($source)) {
            throw new InputException(__('Input data is empty'));
        }

//        $validationResult = $this->stockSourceLinksValidator->validate($links);
//        if (!$validationResult->isValid()) {
//            throw new ValidationException(__('Validation Failed'), null, 0, $validationResult);
//        }

        try {
            $this->save->execute($source);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotSaveException(__('Could not save SourceTypeLinks'), $e);
        }
    }
}
