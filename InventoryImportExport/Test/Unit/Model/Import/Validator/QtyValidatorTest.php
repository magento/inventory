<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Test\Unit\Model\Import\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryImportExport\Model\Import\Sources;
use Magento\InventoryImportExport\Model\Import\Validator\QtyValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QtyValidatorTest extends TestCase
{
    /**
     * @var MockObject|null
     */
    private ?MockObject $validationResultFactory;

    /**
     * @var QtyValidator|null
     */
    private ?QtyValidator $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationResultFactory = $this->createMock(ValidationResultFactory::class);
        $this->model = new QtyValidator($this->validationResultFactory);
    }

    #[DataProvider('validateDataProvider')]
    public function testValidate(array $data, array $errors = []): void
    {
        $rowNumber = 1;
        $result = new ValidationResult([]);
        $this->validationResultFactory
            ->expects($this->once())->method('create')
            ->with(['errors' => $errors])
            ->willReturn($result);
        $this->assertSame($result, $this->model->validate($data, $rowNumber));
    }

    public static function validateDataProvider(): array
    {
        return [
            [
                [Sources::COL_QTY => 1,],
            ],
            [
                [Sources::COL_QTY => 0,],
            ],
            [
                [Sources::COL_QTY => -1,],
            ],
            [
                [Sources::COL_QTY => 0.1,],
            ],
            [
                [Sources::COL_QTY => '1',],
            ],
            [
                [Sources::COL_QTY => '0',],
            ],
            [
                [Sources::COL_QTY => '-1',],
            ],
            [
                [Sources::COL_QTY => '0.1',],
            ],
            [
                [Sources::COL_QTY => 'abc',],
                [__('"%column" contains incorrect value', ['column' => Sources::COL_QTY])],
            ],
            [
                ['qty' => 1,],
                [__('Missing required column "%column"', ['column' => Sources::COL_QTY])],
            ],
        ];
    }
}
