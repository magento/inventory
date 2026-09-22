<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Validator;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\Validators\NoSpaceBeforeAndAfterString;
use Magento\Inventory\Model\Validators\NotAnEmptyString;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Model\SourceItemValidatorInterface;

/**
 * Check that sku is valid
 */
class SkuValidator implements SourceItemValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var NotAnEmptyString
     */
    private $notAnEmptyString;

    /**
     * @var NoSpaceBeforeAndAfterString
     */
    private $noSpaceBeforeAndAfterString;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param NotAnEmptyString $notAnEmptyString
     * @param NoSpaceBeforeAndAfterString $noSpaceBeforeAndAfterString
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        NotAnEmptyString $notAnEmptyString,
        NoSpaceBeforeAndAfterString $noSpaceBeforeAndAfterString,
        ?ProductRepositoryInterface $productRepository = null
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->notAnEmptyString = $notAnEmptyString;
        $this->noSpaceBeforeAndAfterString = $noSpaceBeforeAndAfterString;
        $this->productRepository = $productRepository ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function validate(SourceItemInterface $source): ValidationResult
    {
        $value = $source->getSku();
        $errors = [
            $this->notAnEmptyString->execute(SourceItemInterface::SKU, (string)$value),
            $this->noSpaceBeforeAndAfterString->execute(SourceItemInterface::SKU, (string)$value),
            $this->validateSkuExists((string)$value)
        ];
        $errors = array_merge(...$errors);
        return $this->validationResultFactory->create(['errors' => $errors]);
    }

    /**
     * Validate that product with given SKU exists
     *
     * @param string $sku
     * @return array
     */
    private function validateSkuExists(string $sku): array
    {
        $errors = [];
        if (empty($sku)) {
            return $errors;
        }

        try {
            $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            $errors[] = __('Product with SKU "%1" does not exist.', $sku);
        }

        return $errors;
    }
}
