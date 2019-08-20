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
     * @var SearchRequestBuilderInterface
     */
    private $searchRequestBuilder;

    /**
     * @var GetPickupLocationsInterface
     */
    private $getPickupLocations;

    /**
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param ValidationResultFactory $validationResultFactory
     * @param SearchRequestBuilderInterface $searchRequestBuilder
     * @param GetPickupLocationsInterface $getPickupLocations
     */
    public function __construct(
        WebsiteRepositoryInterface $websiteRepository,
        ValidationResultFactory $validationResultFactory,
        SearchRequestBuilderInterface $searchRequestBuilder,
        GetPickupLocationsInterface $getPickupLocations
    ) {
        $this->websiteRepository = $websiteRepository;
        $this->validationResultFactory = $validationResultFactory;
        $this->searchRequestBuilder = $searchRequestBuilder;
        $this->getPickupLocations = $getPickupLocations;
    }

    /**
     * @inheritdoc
     */
    public function validate(RateRequest $rateRequest): ValidationResult
    {
        $errors = [];

        try {
            $website = $this->websiteRepository->getById((int)$rateRequest->getWebsiteId());

            if (!$this->isAnyPickupLocationAvailable($website->getCode())) {
                $errors[] = __('No Pickup Locations available for Sales Channel %1.', $website->getCode());
            }
        } catch (NoSuchEntityException $exception) {
            $errors[] = __('Can not resolve Sales Channel for Website with id %1.', $rateRequest->getWebsiteId());
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }

    /**
     * Check if at least one Pickup Location is available for the website sales channel.
     *
     * @param string $websiteCode
     *
     * @return bool
     */
    private function isAnyPickupLocationAvailable(string $websiteCode): bool
    {
        $searchRequest = $this->searchRequestBuilder->setScopeType(SalesChannelInterface::TYPE_WEBSITE)
            ->setScopeCode($websiteCode)
            ->setPageSize(1)
            ->create();

        $pickupLocations = $this->getPickupLocations->execute($searchRequest);

        return (bool) $pickupLocations->getTotalCount();
    }
}
