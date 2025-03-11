<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Integration\Source\Validator;

use Magento\Inventory\Model\Source\Validator\CodeValidator;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class CodeValidatorTest extends TestCase
{
    /**
     * @var CodeValidator
     */
    private $validator;

    /**
     * @var SourceInterfaceFactory
     */
    private $sourceFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Bootstrap::getObjectManager()->get(CodeValidator::class);
        $this->sourceFactory = Bootstrap::getObjectManager()->get(SourceInterfaceFactory::class);
    }

    /**
     * @dataProvider dataProvider
     * @param string $sourceCode
     * @param int $errorCount
     * @param array $errorStrings
     */
    public function testValidation($sourceCode, $errorCount, $errorStrings)
    {
        $source = $this->sourceFactory->create(
            [
                'data' => [
                    SourceInterface::SOURCE_CODE => $sourceCode
                ]
            ]
        );

        $result = $this->validator->validate($source);

        $this->assertSame($errorCount === 0, $result->isValid());
        $this->assertCount($errorCount, $result->getErrors());
        $errors = $result->getErrors();
        foreach ($errorStrings as $errorString) {
            $errorText = array_shift($errors);
            $this->assertStringContainsString((string)$errorString, (string)$errorText);
        }
    }

    /**
     * @return array
     */
    public static function dataProvider()
    {
        return [
            'valid code string' => [
                'sourceCode' => 'valid_code',
                'errorCount' => 0,
                'errorStrings' => []
            ],
            'empty value' => [
                'sourceCode' => '',
                'errorCount' => 1,
                'errorStrings' => [
                    'can not be empty'
                ]
            ],
            'whitespace as value' => [
                'sourceCode' => ' ',
                'errorCount' => 2,
                'errorStrings' => [
                    'can not be empty',
                    'can not contain whitespaces'
                ]
            ],
            'value contains whitespace' => [
                'sourceCode' => 'test test',
                'errorCount' => 1,
                'errorStrings' => [
                    'can not contain whitespaces'
                ]
            ],
            'special chars 1' => [
                'sourceCode' => 'some${test}',
                'errorCount' => 1,
                'errorStrings' => [
                    'Validation Failed'
                ]
            ],
            'special chars 2' => [
                'sourceCode' => '${test}',
                'errorCount' => 1,
                'errorStrings' => [
                    'Validation Failed'
                ]
            ],
            'special chars 3' => [
                'sourceCode' => 'foo$::{test}',
                'errorCount' => 1,
                'errorStrings' => [
                    'Validation Failed'
                ]
            ],
            'special chars 4' => [
                'sourceCode' => '${}',
                'errorCount' => 1,
                'errorStrings' => [
                    'Validation Failed'
                ]
            ],
        ];
    }
}
