<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Integration\Source\Validator;

use Magento\Inventory\Model\Source\Validator\NameValidator;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class NameValidatorTest extends TestCase
{
    /**
     * @var NameValidator
     */
    private $validator;

    /**
     * @var SourceInterfaceFactory
     */
    private $sourceFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Bootstrap::getObjectManager()->get(NameValidator::class);
        $this->sourceFactory = Bootstrap::getObjectManager()->get(SourceInterfaceFactory::class);
    }

    /**
     * @dataProvider dataProvider
     * @param string $value
     * @param int $errorCount
     * @param array $errorStrings
     */
    public function testValidation($value, $errorCount, $errorStrings)
    {
        $source = $this->sourceFactory->create(
            [
                'data' => [
                    SourceInterface::NAME => $value
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
    public function dataProvider()
    {
        return [
            'valid code string' => [
                'value' => 'valid_code',
                'errorCount' => 0,
                'errorStrings' => []
            ],
            'empty value' => [
                'value' => '',
                'errorCount' => 1,
                'errorStrings' => [
                    'can not be empty'
                ]
            ],
            'whitespace as value' => [
                'value' => ' ',
                'errorCount' => 1,
                'errorStrings' => [
                    'can not be empty'
                ]
            ],
            'value contains whitespace' => [
                'value' => 'test test',
                'errorCount' => 0,
                'errorStrings' => []
            ],
            'special chars 1' => [
                'value' => 'some${test}',
                'errorCount' => 1,
                'errorStrings' => [
                    'Validation Failed'
                ]
            ],
            'special chars 2' => [
                'value' => '${test}',
                'errorCount' => 1,
                'errorStrings' => [
                    'Validation Failed'
                ]
            ],
            'special chars 3' => [
                'value' => 'foo$::{test}',
                'errorCount' => 1,
                'errorStrings' => [
                    'Validation Failed'
                ]
            ],
            'special chars 4' => [
                'value' => '${}',
                'errorCount' => 1,
                'errorStrings' => [
                    'Validation Failed'
                ]
            ],
        ];
    }
}
