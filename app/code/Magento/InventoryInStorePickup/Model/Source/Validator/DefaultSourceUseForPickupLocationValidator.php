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
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;

/**
 * Validate that Default Source is not used as Pickup Location.
 */
class DefaultSourceUseForPickupLocationValidator implements SourceValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * @param SourceInterface $source
     *
     * @return ValidationResult
     */
    public function validate(SourceInterface $source): ValidationResult
    {
        $errors = [];

        if ($this->isDefaultSource($source) && $this->isUsedAsPickupLocation($source)) {
            $errors[] = __('The Default Source can not be used for In-Store Pickup Delivery.');
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }

    /**
     * Check if provided Source is Default Source.
     *
     * @param SourceInterface $source
     *
     * @return bool
     */
    private function isDefaultSource(SourceInterface $source): bool
    {
        return $source->getSourceCode() === $this->defaultSourceProvider->getCode();
    }

    /**
     * Check if Source has been marked as Pickup Location.
     *
     * @param SourceInterface $source
     *
     * @return bool
     */
    private function isUsedAsPickupLocation(SourceInterface $source): bool
    {
        return !$source->getExtensionAttributes() || $source->getExtensionAttributes()->getIsPickupLocationActive();
    }
}
