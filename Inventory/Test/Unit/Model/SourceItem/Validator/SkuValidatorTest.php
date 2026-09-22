<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Unit\Model\SourceItem\Validator;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\SourceItem;
use Magento\Inventory\Model\SourceItem\Validator\SkuValidator;
use Magento\Inventory\Model\Validators\NoSpaceBeforeAndAfterString;
use Magento\Inventory\Model\Validators\NotAnEmptyString;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SkuValidatorTest extends TestCase
{
    /**
     * @var ValidationResultFactory|MockObject
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
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var SourceItem|MockObject
     */
    private $sourceItemMock;

    /**
     * @var SkuValidator
     */
    private $skuValidator;

    protected function setUp(): void
    {
        $this->validationResultFactory = $this->createMock(ValidationResultFactory::class);
        $this->notAnEmptyString = $this->createMock(NotAnEmptyString::class);
        $this->noSpaceBeforeAndAfterString = $this->createMock(NoSpaceBeforeAndAfterString::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->sourceItemMock = $this->getMockBuilder(SourceItem::class)->disableOriginalConstructor()
            ->onlyMethods(['getSku', 'getSourceCode', 'getQuantity', 'getStatus', 'getData', 'setData'])->getMock();
        $this->skuValidator = new SkuValidator(
            $this->validationResultFactory,
            $this->notAnEmptyString,
            $this->noSpaceBeforeAndAfterString,
            $this->productRepository
        );
    }

    /**
     * @return array
     */
    public static function sourceDataProvider(): array
    {
        return [
            [
                [
                    "sku" => "4444454",
                    "quantity" => 30,
                    "status" => 1,
                    "execute" => [],
                    "is_string_whitespace" => 0
                ]
            ],
            [
                [
                    "sku" => "4444454      ",
                    "quantity" => 30,
                    "status" => 1,
                    "execute" => [new Phrase('"%field" can not contain leading or trailing spaces.', ['sku'])],
                    "is_string_whitespace" => 1
                ]
            ]
        ];
    }

    /**
     * @param array $source
     * @return void
     */
    #[DataProvider('sourceDataProvider')]
    public function testValidate(array $source): void
    {
        $this->sourceItemMock->expects($this->atLeastOnce())->method('getSku')
        ->willReturn($source['sku']);
        $errors = [$source['execute']];
        $errors = array_merge(...$errors);
        $this->noSpaceBeforeAndAfterString->method('execute')->willReturn($source['execute']);

        // Mock product repository to return existing product
        $product = $this->createMock(ProductInterface::class);
        $this->productRepository->method('get')->willReturn($product);

        $this->validationResultFactory->method('create')->with(
            ['errors' => $errors]
        )->willReturn(new ValidationResult($errors));
        $result = $this->skuValidator->validate($this->sourceItemMock);
        if ($source['is_string_whitespace']) {
            foreach ($result->getErrors() as $error) {
                $this->assertEquals('"%field" can not contain leading or trailing spaces.', $error->getText());
            }
        } else {
            $this->assertEmpty($result->getErrors());
        }
    }

    /**
     * Test validation when SKU does not exist
     *
     * @return void
     */
    public function testValidateNonExistentSku(): void
    {
        $sku = 'non_existent_sku';
        $this->sourceItemMock->expects($this->atLeastOnce())->method('getSku')
            ->willReturn($sku);

        $this->notAnEmptyString->method('execute')->willReturn([]);
        $this->noSpaceBeforeAndAfterString->method('execute')->willReturn([]);

        // Mock product repository to throw NoSuchEntityException
        $this->productRepository->expects($this->once())
            ->method('get')
            ->with($sku)
            ->willThrowException(new NoSuchEntityException(__('Product not found')));

        $expectedError = __('Product with SKU "%1" does not exist.', $sku);
        $errors = [$expectedError];

        $this->validationResultFactory->method('create')->with(
            ['errors' => $errors]
        )->willReturn(new ValidationResult($errors));

        $result = $this->skuValidator->validate($this->sourceItemMock);

        $this->assertCount(1, $result->getErrors());
        $errorMessage = $result->getErrors()[0];
        $this->assertInstanceOf(Phrase::class, $errorMessage);
        $this->assertEquals('Product with SKU "%1" does not exist.', $errorMessage->getText());
        $this->assertEquals(['non_existent_sku'], $errorMessage->getArguments());
    }
}
