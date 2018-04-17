<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\Stock\Validator;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\Stock\Command\GetListInterface;
use Magento\InventoryApi\Api\Data\StockInterface;

/**
 * Check that name is unique.
 */
class UniqueNameValidator implements StockValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var GetListInterface
     */
    private $getList;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param GetListInterface $getList
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        GetListInterface $getList,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->getList = $getList;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritdoc
     */
    public function validate(StockInterface $stock): ValidationResult
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(StockInterface::NAME, $stock->getName())
            ->addFilter(StockInterface::STOCK_ID, $stock->getStockId(), 'neq')
            ->create();
        $stockSearchResults = $this->getList->execute($searchCriteria);

        if (!empty($stockSearchResults->getItems())) {
            $errors[] = __('"%field" value should be unique.', ['field' => StockInterface::NAME]);
        } else {
            $errors = [];
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}
