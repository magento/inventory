<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\Source\Validator;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\Source\Command\GetListInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * Check, whether source with given source code already exists.
 */
class UniqueCodeValidator implements SourceValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var GetListInterface
     */
    private $getList;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetListInterface $getList
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetListInterface $getList
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getList = $getList;
    }

    /**
     * @param SourceInterface $source
     * @return ValidationResult
     */
    public function validate(SourceInterface $source): ValidationResult
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceInterface::SOURCE_CODE, $source->getSourceCode())
            ->create();
        $stockSearchResults = $this->getList->execute($searchCriteria);

        if (!empty($stockSearchResults->getItems())) {
            $errors[] = __('"%field" value should be unique.', ['field' => SourceInterface::SOURCE_CODE]);
        } else {
            $errors = [];
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}
