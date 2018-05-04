<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\StockSourceLink\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\StockSourceLink\Validator\StockSourceLinkValidatorInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryCatalog\Api\DefaultStockProviderInterface;

class AssignNonDefaultSourceToDefaultStockValidator implements StockSourceLinkValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * @inheritdoc
     */
    public function validate(StockSourceLinkInterface $link): ValidationResult
    {
        $errors = [];
        if ($link->getStockId() === $this->defaultStockProvider->getId()) {
            $errors[] = __('Can not save link related to Default Stock');
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }

}
