<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShipping\Model\Carrier\Validation;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\Validation\RequestValidatorInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * @inheritdoc
 */
class SalesChannelValidator implements RequestValidatorInterface
{
    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param ValidationResultFactory $validationResultFactory
     */
    public function __construct(
        WebsiteRepositoryInterface $websiteRepository,
        ValidationResultFactory $validationResultFactory
    ) {
        $this->websiteRepository = $websiteRepository;
        $this->validationResultFactory = $validationResultFactory;
    }

    /**
     * @inheritdoc
     */
    public function validate(RateRequest $rateRequest): ValidationResult
    {
        $errors = [];

        try {
            $this->websiteRepository->getById((int)$rateRequest->getWebsiteId());
        } catch (NoSuchEntityException $exception) {
            $errors[] = __('Can not resolve Sales Channel for Website with id %1.', $rateRequest->getWebsiteId());
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}
