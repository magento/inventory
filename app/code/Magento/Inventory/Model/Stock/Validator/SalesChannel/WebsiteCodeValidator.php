<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\Stock\Validator\SalesChannel;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventoryApi\Model\SalesChannelValidatorInterface;

/**
 * Check that website code is valid
 */
class WebsiteCodeValidator implements SalesChannelValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @param ValidationResultFactory    $validationResultFactory
     * @param WebsiteRepositoryInterface $websiteRepository
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        WebsiteRepositoryInterface $websiteRepository
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->websiteRepository = $websiteRepository;
    }

    /**
     * @inheritdoc
     */
    public function validate(SalesChannelInterface $salesChannel): ValidationResult
    {
        $errors = [];
        try {
            $this->websiteRepository->get($salesChannel->getCode());
        } catch (NoSuchEntityException $e) {
            $errors[] = __(
                'Website with code "%code" does not exist. Cannot add sales channel.',
                ['code' => $salesChannel->getCode()]
            );
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}
