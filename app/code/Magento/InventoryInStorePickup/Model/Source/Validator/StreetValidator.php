<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Source\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Model\SourceValidatorInterface;
use Magento\InventoryInStorePickup\Model\Source\GetIsPickupLocationActive;

/**
 * Check that street is set for Pickup Location.
 */
class StreetValidator implements SourceValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var GetIsPickupLocationActive
     */
    private $getIsPickupLocationActive;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param GetIsPickupLocationActive $getIsPickupLocationActive
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        GetIsPickupLocationActive $getIsPickupLocationActive
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->getIsPickupLocationActive = $getIsPickupLocationActive;
    }

    /**
     * @inheritdoc
     */
    public function validate(SourceInterface $source): ValidationResult
    {
        $value = (string)$source->getStreet();
        $errors = [];

        if ($this->getIsPickupLocationActive->execute($source) && '' === trim($value)) {
            $errors[] = __('"%field" can not be empty for Pickup Location.', ['field' => SourceInterface::STREET]);
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}
