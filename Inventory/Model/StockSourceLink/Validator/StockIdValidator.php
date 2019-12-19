<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\StockSourceLink\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\Validators\NotAnEmptyString;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Model\StockSourceLinkValidatorInterface;

/**
 * Check that stock ID is valid
 */
class StockIdValidator implements StockSourceLinkValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var NotAnEmptyString
     */
    private $notAnEmptyString;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param NotAnEmptyString $notAnEmptyString
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        NotAnEmptyString $notAnEmptyString
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->notAnEmptyString = $notAnEmptyString;
    }

    /**
     * @inheritdoc
     */
    public function validate(StockSourceLinkInterface $link): ValidationResult
    {
        $value = (string)$link->getStockId();
        $errors = [
            $this->notAnEmptyString->execute(StockSourceLinkInterface::STOCK_ID, $value)
        ];
        $errors = !empty($errors) ? array_merge(...$errors) : $errors;

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}
