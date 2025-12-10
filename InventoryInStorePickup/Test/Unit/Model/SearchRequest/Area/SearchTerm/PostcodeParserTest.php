<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Unit\Model\SearchRequest\Area\SearchTerm;

use Magento\Directory\Model\Country\Postcode\ValidatorInterface;
use Magento\Framework\DataObject;
use Magento\InventoryInStorePickup\Model\SearchRequest\Area\SearchTerm\PostcodeParser;
use Magento\InventoryInStorePickupApi\Model\SearchRequest\Area\SearchTerm\DelimiterConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PostcodeParserTest extends TestCase
{
    /**
     * @var PostcodeParser
     */
    private $model;

    /**
     * @var ValidatorInterface|MockObject
     */
    private $validatorMock;

    /**
     * @var DelimiterConfig|MockObject
     */
    private $delimiterConfigMock;

    protected function setUp(): void
    {
        $this->validatorMock = $this->createMock(ValidatorInterface::class);
        $this->delimiterConfigMock = $this->createMock(DelimiterConfig::class);
        $this->model = new PostcodeParser($this->validatorMock, $this->delimiterConfigMock);
    }

    #[
        DataProvider('executeDataProvider'),
    ]
    public function testExecute(string $searchTerm, string $country, bool $isValid, string $postcode): void
    {
        $this->delimiterConfigMock->expects($this->atLeastOnce())
            ->method('getDelimiter')
            ->willReturn(':');
        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->willReturn($isValid);

        $result = new DataObject();
        $result->setData('country', $country);
        $this->model->execute($searchTerm, $result);
        $this->assertEquals($postcode, $result->getData('postcode'));
    }

    public static function executeDataProvider(): array
    {
        return [
            ['12345', 'AS', true, '12345'],
            ['1234:AT', 'AT', true, '1234'],
            ['11123', 'AX', false, ''],
            ['AA12345:BB', 'BB', false, ''],
        ];
    }

    public function testExecuteWithException(): void
    {
        $this->delimiterConfigMock->expects($this->atLeastOnce())
            ->method('getDelimiter')
            ->willReturn(':');
        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->willThrowException(new \InvalidArgumentException());

        $result = new DataObject();
        $result->setData('country', 'UU');
        $this->model->execute('-', $result);
        $this->assertEquals('', $result->getData('postcode'));
    }
}
