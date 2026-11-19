<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Test\Unit\Model\SourceItem\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfiguration\Model\SourceItem\Validator\ProductTypeManagementAllowedValidator;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductTypeManagementAllowedValidatorTest extends TestCase
{
    /**
     * @var GetProductTypesBySkusInterface|MockObject
     */
    private $getProductTypesBySkus;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface|MockObject
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var ValidationResultFactory|MockObject
     */
    private $validationResultFactory;

    /**
     * @var ProductTypeManagementAllowedValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->getProductTypesBySkus = $this->createMock(GetProductTypesBySkusInterface::class);
        $this->isSourceItemManagementAllowedForProductType = $this->createMock(
            IsSourceItemManagementAllowedForProductTypeInterface::class
        );
        $this->validationResultFactory = $this->createMock(ValidationResultFactory::class);
        $this->validationResultFactory->method('create')
            ->willReturnCallback(fn (array $args) => new ValidationResult($args['errors']));

        $this->validator = new ProductTypeManagementAllowedValidator(
            $this->getProductTypesBySkus,
            $this->isSourceItemManagementAllowedForProductType,
            $this->validationResultFactory
        );
    }

    /**
     * A source item for a product type where management is not allowed is rejected.
     *
     * @return void
     */
    public function testRejectsSourceItemForDisallowedProductType(): void
    {
        $sourceItem = $this->createMock(SourceItemInterface::class);
        $sourceItem->method('getSku')->willReturn('configurable-sku');

        $this->getProductTypesBySkus->method('execute')
            ->with(['configurable-sku'])
            ->willReturn(['configurable-sku' => 'configurable']);
        $this->isSourceItemManagementAllowedForProductType->method('execute')
            ->with('configurable')
            ->willReturn(false);

        $result = $this->validator->validate($sourceItem);

        self::assertNotEmpty($result->getErrors());
    }

    /**
     * A source item for a product type where management is allowed passes.
     *
     * @return void
     */
    public function testAllowsSourceItemForAllowedProductType(): void
    {
        $sourceItem = $this->createMock(SourceItemInterface::class);
        $sourceItem->method('getSku')->willReturn('simple-sku');

        $this->getProductTypesBySkus->method('execute')
            ->with(['simple-sku'])
            ->willReturn(['simple-sku' => 'simple']);
        $this->isSourceItemManagementAllowedForProductType->method('execute')
            ->with('simple')
            ->willReturn(true);

        $result = $this->validator->validate($sourceItem);

        self::assertEmpty($result->getErrors());
    }

    /**
     * A SKU that cannot be resolved to a product type (e.g. not yet persisted) is not rejected -
     * there is nothing to validate against yet, and other validators/consumers own that case.
     *
     * @return void
     */
    public function testAllowsSourceItemWhenProductTypeCannotBeResolved(): void
    {
        $sourceItem = $this->createMock(SourceItemInterface::class);
        $sourceItem->method('getSku')->willReturn('unknown-sku');

        $this->getProductTypesBySkus->method('execute')
            ->with(['unknown-sku'])
            ->willReturn([]);
        $this->isSourceItemManagementAllowedForProductType->expects(self::never())->method('execute');

        $result = $this->validator->validate($sourceItem);

        self::assertEmpty($result->getErrors());
    }
}
