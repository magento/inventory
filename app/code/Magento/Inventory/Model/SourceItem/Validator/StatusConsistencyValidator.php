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
class StatusConsistencyValidator implements SourceItemValidatorInterface
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
    public function validate(SourceItemInterface $sourceItem): ValidationResult
    {
        $errors = [];
        $quantity = $sourceItem->getQuantity();
        $status = $sourceItem->getStatus();

        if (is_numeric($quantity)
            && (float)$quantity <= 0
            && (int)$status === SourceItemInterface::STATUS_IN_STOCK
        ) {
            $statusOptions = $this->sourceItemStatus->toOptionArray();
            $labels = array_column($statusOptions, 'label', 'value');
            $errors[] = __(
                'Product cannot have "%status" "%in_stock" while product "%quantity" equals or below zero',
                [
                    'status' => SourceItemInterface::STATUS,
                    'in_stock' => $labels[SourceItemInterface::STATUS_IN_STOCK],
                    'quantity' => SourceItemInterface::QUANTITY
                ]
            );
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}
