<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Import\Validator;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryImportExport\Model\Import\Sources;

/**
 * Extension point for row validation
 */
class QtyValidator implements ValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @throws LocalizedException
     */
    public function __construct(ValidationResultFactory $validationResultFactory)
    {
        $this->validationResultFactory = $validationResultFactory;
    }

    /**
     * @inheritdoc
     */
    public function validate(array $rowData, int $rowNumber)
    {
        $errors = [];

        if (!isset($rowData[Sources::COL_QTY])) {
            $errors[] = __('Missing required column "%column"', ['column' => Sources::COL_QTY]);
        } elseif (!is_numeric($rowData[Sources::COL_QTY])) {
            $errors[] = __('"%column" contains incorrect value', ['column' => Sources::COL_QTY]);
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}
