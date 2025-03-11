<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Unit\Model\SourceItem\Validator;

use Magento\Framework\Phrase;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\SourceItem;
use Magento\Inventory\Model\SourceItem\Validator\SkuValidator;
use Magento\Inventory\Model\Validators\NoSpaceBeforeAndAfterString;
use Magento\Inventory\Model\Validators\NotAnEmptyString;
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
        $this->sourceItemMock = $this->getMockBuilder(SourceItem::class)->disableOriginalConstructor()
            ->onlyMethods(['getSku', 'getSourceCode', 'getQuantity', 'getStatus', 'getData', 'setData'])->getMock();
        $this->skuValidator = new SkuValidator(
            $this->validationResultFactory,
            $this->notAnEmptyString,
            $this->noSpaceBeforeAndAfterString
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
     * @dataProvider sourceDataProvider
     * @param array $source
     * @return void
     */
    public function testValidate(array $source): void
    {
        $this->sourceItemMock->expects($this->atLeastOnce())->method('getSku')
        ->willReturn($source['sku']);
        $errors = [$source['execute']];
        $errors = array_merge(...$errors);
        $this->noSpaceBeforeAndAfterString->method('execute')->willReturn($source['execute']);
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
}
