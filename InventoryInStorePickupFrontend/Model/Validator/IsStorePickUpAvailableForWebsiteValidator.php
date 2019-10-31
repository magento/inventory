<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupFrontend\Model\Validator;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Verify, if store pick-up available for given website.
 */
class IsStorePickUpAvailableForWebsiteValidator
{
    private const CONFIG_PATH = 'carriers/in_store/active';

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
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param SearchRequestBuilderInterface $searchRequestBuilder
     * @param GetPickupLocationsInterface $getPickupLocations
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        SearchRequestBuilderInterface $searchRequestBuilder,
        GetPickupLocationsInterface $getPickupLocations,
        ScopeConfigInterface $config
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->searchRequestBuilder = $searchRequestBuilder;
        $this->getPickupLocations = $getPickupLocations;
        $this->config = $config;
    }

    /**
     * Get is store pick-up available for given website.
     *
     * @param string $websiteCode
     * @return bool
     */
    public function execute(string $websiteCode): bool
    {
        if (!$this->config->getValue(self::CONFIG_PATH, ScopeInterface::SCOPE_WEBSITE, $websiteCode)) {
            return false;
        }
        $searchRequest = $this->searchRequestBuilder->setScopeType(ScopeInterface::SCOPE_WEBSITE)
            ->setScopeCode($websiteCode)
            ->setPageSize(1)
            ->create();
        $pickupLocations = $this->getPickupLocations->execute($searchRequest);

        return (bool)$pickupLocations->getTotalCount();
    }
}
