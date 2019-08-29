<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\Source\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Inventory\Model\Source\Command\GetListInterface;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Model\SourceValidatorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Check that code is valid
 */
class CodeValidator implements SourceValidatorInterface
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
    private $getSourceList;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetListInterface $getSourceList
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetListInterface $getSourceList
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getSourceList = $getSourceList;
    }

    /**
     * @inheritdoc
     */
    public function validate(SourceInterface $source): ValidationResult
    {
        $value = (string)$source->getSourceCode();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceInterface::SOURCE_CODE, $value)
            ->create();
        $sourceSearchResults = $this->getSourceList->execute($searchCriteria);
        if ($sourceSearchResults->getTotalCount()) {
            $errors[] = __('"%field" value should be unique.', ['field' => SourceInterface::SOURCE_CODE]);
        } elseif ('' === trim($value)) {
            $errors[] = __('"%field" can not be empty.', ['field' => SourceInterface::SOURCE_CODE]);
        } elseif (preg_match('/\s/', $value)) {
            $errors[] = __('"%field" can not contain whitespaces.', ['field' => SourceInterface::SOURCE_CODE]);
        } elseif (preg_match('/\$[:]*{(.)*}/', $value)) {
            $errors[] = __('Validation Failed');
        } else {
            $errors = [];
        }
        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}
