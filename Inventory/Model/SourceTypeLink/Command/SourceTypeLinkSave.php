<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceTypeLink\Command;

use Magento\InventoryApi\Api\Data\SourceTypeLinkInterface;
use Magento\InventoryApi\Api\SourceTypeLinkSaveInterface;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink as SourceTypeLinkResourceModel;
use Magento\InventoryApi\Model\SourceTypeLinkValidatorInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Validation\ValidationException;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SourceTypeLinkSave implements SourceTypeLinkSaveInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SourceTypeLinkResourceModel
     */
    private $sourceTypeLinkResource;

    /**
     * @var SourceTypeLinkValidatorInterface
     */
    private $sourceTypeLinkValidator;

    /**
     * @param SourceTypeLinkResourceModel $sourceTypeLinkResource
     * @param SourceTypeLinkValidatorInterface $sourceTypeLinkValidator
     * @param LoggerInterface $logger
     */
    public function __construct(
        SourceTypeLinkResourceModel $sourceTypeLinkResource,
        SourceTypeLinkValidatorInterface $sourceTypeLinkValidator,
        LoggerInterface $logger
    ) {
        $this->sourceTypeLinkResource = $sourceTypeLinkResource;
        $this->sourceTypeLinkValidator = $sourceTypeLinkValidator;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(SourceTypeLinkInterface $link): void
    {
        if (empty($link)) {
            throw new InputException(__('Input data is empty'));
        }

        $validationResult = $this->sourceTypeLinkValidator->validate($link);
        if (!$validationResult->isValid()) {
            throw new ValidationException(__('Validation Failed'), null, 0, $validationResult);
        }

        try {
            $this->sourceTypeLinkResource->save($link);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotSaveException(__('Could not save SourceTypeLink'), $e);
        }
    }
}
