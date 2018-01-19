<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\OptionSource\SourceItemStatus;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Check that if product quantity <= 0 status is not "In Stock".
 */
class ConsistencyValidator implements SourceItemValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var SourceItemStatus
     */
    private $sourceItemStatus;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param SourceItemStatus $sourceItemStatus
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        SourceItemStatus $sourceItemStatus
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->sourceItemStatus = $sourceItemStatus;
    }

    /**
     * @inheritdoc
     */
    public function validate(SourceItemInterface $source): ValidationResult
    {
        $status = $source->getStatus();
        $quantity = $source->getQuantity();
        $statusOptions = $this->sourceItemStatus->toOptionArray();
        $label = '';
        foreach ($statusOptions as $statusOption) {
            if ($statusOption['value'] === SourceItemInterface::STATUS_IN_STOCK) {
                $label = (string)$statusOption['label'];
                break;
            }
        }
        $errors = [];
        if ((float)$quantity <= 0 && (int)$status === SourceItemInterface::STATUS_IN_STOCK) {
            $errors[] = __(
                'Product cannot have "%status" "%in_stock" while product "%quantity" equals or below zero',
                [
                    'status' => SourceItemInterface::STATUS,
                    'in_stock' => $label,
                    'quantity' => SourceItemInterface::QUANTITY,

                ]
            );
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}
