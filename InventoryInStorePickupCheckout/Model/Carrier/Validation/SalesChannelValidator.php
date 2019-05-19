<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupCheckout\Model\Carrier\Validation;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryInStorePickupApi\Api\GetIsAnyPickupLocationAvailableInterface;
use Magento\InventoryInStorePickupCheckoutApi\Model\Carrier\Validation\RequestValidatorInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * @inheritdoc
 */
class SalesChannelValidator implements RequestValidatorInterface
{
    /**
     * @var SalesChannelInterfaceFactory
     */
    private $salesChannelInterfaceFactory;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var GetIsAnyPickupLocationAvailableInterface
     */
    private $getIsAnyPickupLocationAvailable;

    /**
     * @param SalesChannelInterfaceFactory $salesChannelInterfaceFactory
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param ValidationResultFactory $validationResultFactory
     * @param GetIsAnyPickupLocationAvailableInterface $getIsAnyPickupLocationAvailable
     */
    public function __construct(
        SalesChannelInterfaceFactory $salesChannelInterfaceFactory,
        WebsiteRepositoryInterface $websiteRepository,
        ValidationResultFactory $validationResultFactory,
        GetIsAnyPickupLocationAvailableInterface $getIsAnyPickupLocationAvailable
    ) {
        $this->salesChannelInterfaceFactory = $salesChannelInterfaceFactory;
        $this->websiteRepository = $websiteRepository;
        $this->validationResultFactory = $validationResultFactory;
        $this->getIsAnyPickupLocationAvailable = $getIsAnyPickupLocationAvailable;
    }

    /**
     * @inheritdoc
     */
    public function validate(RateRequest $rateRequest): ValidationResult
    {
        $errors = [];

        try {
            $salesChannel = $this->getSalesChannel((int)$rateRequest->getWebsiteId());
            $isAnyPickupLocationAvailable = $this->getIsAnyPickupLocationAvailable->execute($salesChannel);

            if (!$isAnyPickupLocationAvailable) {
                $errors[] = __('No Pickup Locations available for Sales Channel %1.', $salesChannel->getCode());
            }
        } catch (NoSuchEntityException $exception) {
            $errors[] = __('Can not resolve Sales Channel for Website with id %1.', $rateRequest->getWebsiteId());
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }

    /**
     * Get Sales Channel for provided website id.
     *
     * @param int $websiteId
     *
     * @return SalesChannelInterface
     * @throws NoSuchEntityException
     */
    private function getSalesChannel(int $websiteId): SalesChannelInterface
    {
        $website = $this->websiteRepository->getById($websiteId);
        $salesChannel = $this->salesChannelInterfaceFactory->create(
            [
                'data' => [
                    SalesChannelInterface::CODE => $website->getCode(),
                    SalesChannelInterface::TYPE => SalesChannelInterface::TYPE_WEBSITE
                ]
            ]
        );

        return $salesChannel;
    }
}
