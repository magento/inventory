<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\Stock\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Model\StockValidatorInterface;

/**
 * Check that Sales Channel is valid
 */
class SalesChannelValidator implements StockValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var array
     */
    private $salesChannelValidators;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param array                   $salesChannelValidators
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        array $salesChannelValidators = []
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->salesChannelValidators = $salesChannelValidators;
    }

    /**
     * @inheritdoc
     */
    public function validate(StockInterface $stock): ValidationResult
    {
        $errors = [];
        $salesChannels = $stock->getExtensionAttributes()->getSalesChannels();
        if (!empty($salesChannels) && is_array($salesChannels)) {
            foreach ($salesChannels as $saleChannel) {
                if (!empty($saleChannel->getType()
                    && array_key_exists($saleChannel->getType(), $this->salesChannelValidators))) {
                    $validator = $this->salesChannelValidators[$saleChannel->getType()];
                    $validationResult = $validator->validate($saleChannel);
                    if (!$validationResult->isValid()) {
                        $errors = array_merge($errors, $validationResult->getErrors());
                    }
                }
            }
        }
        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}
