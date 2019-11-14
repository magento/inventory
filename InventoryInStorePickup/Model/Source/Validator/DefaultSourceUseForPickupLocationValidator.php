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
     * @inheritdoc
     */
    public function validate(SourceInterface $source): ValidationResult
    {
        $errors = [];

        if ($source->getSourceCode() === $this->defaultSourceProvider->getCode() &&
            (!$source->getExtensionAttributes() || $source->getExtensionAttributes()->getIsPickupLocationActive())
        ) {
            $errors[] = __('The Default Source can not be used for In-Store Pickup Delivery.');
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}
