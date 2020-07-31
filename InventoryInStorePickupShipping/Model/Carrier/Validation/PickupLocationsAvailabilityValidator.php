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
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\ProductInfoInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestExtensionFactory;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestExtensionInterface;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\Validation\RequestValidatorInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Validate that Pickup Locations available for the Rate Request.
 */
class PickupLocationsAvailabilityValidator implements RequestValidatorInterface
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
     * @var SearchRequestExtensionFactory
     */
    private $searchRequestExtensionFactory;

    /**
     * @var ProductInfoInterfaceFactory
     */
    private $productInfoFactory;

    /**
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param ValidationResultFactory $validationResultFactory
     * @param SearchRequestBuilderInterface $searchRequestBuilder
     * @param GetPickupLocationsInterface $getPickupLocations
     * @param SearchRequestExtensionFactory $searchRequestExtensionFactory ,
     * @param ProductInfoInterfaceFactory $productInfoFactory
     */
    public function __construct(
        WebsiteRepositoryInterface $websiteRepository,
        ValidationResultFactory $validationResultFactory,
        SearchRequestBuilderInterface $searchRequestBuilder,
        GetPickupLocationsInterface $getPickupLocations,
        SearchRequestExtensionFactory $searchRequestExtensionFactory,
        ProductInfoInterfaceFactory $productInfoFactory
    ) {
        $this->websiteRepository = $websiteRepository;
        $this->validationResultFactory = $validationResultFactory;
        $this->searchRequestBuilder = $searchRequestBuilder;
        $this->getPickupLocations = $getPickupLocations;
        $this->searchRequestExtensionFactory = $searchRequestExtensionFactory;
        $this->productInfoFactory = $productInfoFactory;
    }

    /**
     * @inheritdoc
     *
     * @throws NoSuchEntityException
     */
    public function validate(RateRequest $rateRequest): ValidationResult
    {
        $errors = [];

        if (!$this->isAnyPickupLocationAvailable($rateRequest)) {
            $errors[] = __('No Pickup Locations available to satisfy the Rate Request.');
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }

    /**
     * Check if at least one Pickup Location satisfy Rate Request.
     *
     * @param RateRequest $rateRequest
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isAnyPickupLocationAvailable(RateRequest $rateRequest): bool
    {
        $website = $this->websiteRepository->getById((int)$rateRequest->getWebsiteId());

        $extensionAttributes = $this->getSearchRequestExtension($rateRequest->getAllItems());

        $searchRequest = $this->searchRequestBuilder->setScopeType(SalesChannelInterface::TYPE_WEBSITE)
            ->setScopeCode($website->getCode())
            ->setSearchRequestExtension($extensionAttributes)
            ->setPageSize(1)
            ->create();

        $pickupLocations = $this->getPickupLocations->execute($searchRequest);

        return $pickupLocations->getTotalCount() !== 0;
    }

    /**
     * Prepare and return Search Request Extension.
     *
     * @param Item[] $items
     *
     * @return SearchRequestExtensionInterface
     */
    private function getSearchRequestExtension(array $items): SearchRequestExtensionInterface
    {
        $productsInfo = [];
        foreach ($items as $item) {
            if (!empty($item->getChildren())) {
                continue;
            }

            $productsInfo[] = $this->productInfoFactory->create(['sku' => $item->getSku()]);
        }

        $extensionAttributes = $this->searchRequestExtensionFactory->create();
        $extensionAttributes->setProductsInfo($productsInfo);

        return $extensionAttributes;
    }
}
