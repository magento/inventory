<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration\Extension;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class InventorySourceExtensionTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var SourceRepositoryInterface */
    private $sourceRepository;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->sourceRepository = $this->objectManager->get(SourceRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_addresses.php
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function testSaveDefaultSourceAsPickupLocation()
    {
        $this->expectException(\Magento\Framework\Validation\ValidationException::class);
        $this->expectExceptionMessage('Validation Failed');
        $source = $this->sourceRepository->get('default');
        $source->getExtensionAttributes()->setIsPickupLocationActive(true);
        $this->sourceRepository->save($source);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     *
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function testSaveSourceAsPickupLocationWithoutStreet()
    {
        $this->expectException(\Magento\Framework\Validation\ValidationException::class);
        $this->expectExceptionMessage('Validation Failed');
        $source = $this->sourceRepository->get('eu-1');
        $source->getExtensionAttributes()->setIsPickupLocationActive(true);
        $source->setCity('Some City');
        $this->sourceRepository->save($source);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     *
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function testSaveSourceAsPickupLocationWithoutCity()
    {
        $this->expectException(\Magento\Framework\Validation\ValidationException::class);
        $this->expectExceptionMessage('Validation Failed');
        $source = $this->sourceRepository->get('eu-2');
        $source->getExtensionAttributes()->setIsPickupLocationActive(true);
        $source->setStreet('Some Street');
        $this->sourceRepository->save($source);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_addresses.php
     */
    public function testGetListOfSourcesWithPickupLocationExtensionAfterSave(): void
    {
        $pickupLocationConfig = [
            'default' => ['active' => false, 'name' => 'default', 'desc' => 'default'],
            'eu-1' => ['active' => true, 'name' => 'EU-source-1', 'desc' => null],
            'eu-2' => ['active' => true, 'name' => 'zzz', 'desc' => null],
            'eu-3' => ['active' => false, 'name' => 'EU-source-3', 'desc' => 'zzz1'],
            'eu-disabled' => ['active' => false, 'name' => 'EU-source-disabled', 'desc' => null],
            'us-1' => ['active' => true, 'name' => '666', 'desc' => null],
        ];

        $searchResult = $this->sourceRepository->getList();

        /** @var SourceInterface $item */
        foreach ($searchResult->getItems() as $item) {
            $item->getExtensionAttributes()
                 ->setIsPickupLocationActive($pickupLocationConfig[$item->getSourceCode()]['active'])
                 ->setFrontendDescription($pickupLocationConfig[$item->getSourceCode()]['desc'])
                 ->setFrontendName($pickupLocationConfig[$item->getSourceCode()]['name']);
            $this->sourceRepository->save($item);
        }

        $searchResult = $this->sourceRepository->getList();

        $pickupLocationsStatus = [];

        foreach ($searchResult->getItems() as $item) {
            $extension = $item->getExtensionAttributes();
            $pickupLocationsStatus[$item->getSourceCode()]['active'] = $extension->getIsPickupLocationActive();
            $pickupLocationsStatus[$item->getSourceCode()]['name'] = $extension->getFrontendName();
            $pickupLocationsStatus[$item->getSourceCode()]['desc'] = $extension->getFrontendDescription();
        }

        $this->assertEquals($pickupLocationConfig, $pickupLocationsStatus);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_addresses.php
     *
     * @dataProvider dataProvider
     *
     * @param array $data
     * @param string $sourceCode
     * @param array $expected
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws ValidationException
     */
    public function testGetSourceWithPickupLocationExtensionAfterSave(array $data, string $sourceCode, array $expected)
    {
        $source = $this->sourceRepository->get($sourceCode);
        $source->getExtensionAttributes()
               ->setIsPickupLocationActive($data['is_active'])
               ->setFrontendName($data['name'])
               ->setFrontendDescription($data['description']);
        $this->sourceRepository->save($source);

        $source = $this->sourceRepository->get($sourceCode);
        $this->assertEquals($expected['is_active'], $source->getExtensionAttributes()->getIsPickupLocationActive());
        $this->assertEquals($expected['name'], $source->getExtensionAttributes()->getFrontendName());
        $this->assertEquals($expected['description'], $source->getExtensionAttributes()->getFrontendDescription());
    }

    /**
     * Test data sets.
     *
     * @return array
     */
    public static function dataProvider(): array
    {
        return [
            [ /* Data set #0. Default Source. */
                [
                    'name' => 'default',
                    'description' => 'default',
                    'is_active' => false
                ],
                'default',
                [
                    'name' => 'default',
                    'description' => 'default',
                    'is_active' => false
                ]
            ],
            [ /* Data set #1. Save Frontend Name and without Description. */
                [
                    'name' => 'EU-source-1',
                    'description' => null,
                    'is_active' => true
                ],
                'eu-1',
                [
                    'name' => 'EU-source-1',
                    'description' => null,
                    'is_active' => true
                ]
            ],
            [ /* Data set #2. Save custom Frontend Name and without Description. */
                [
                    'name' => 'zzz',
                    'description' => null,
                    'is_active' => true
                ],
                'eu-2',
                [
                    'name' => 'zzz',
                    'description' => null,
                    'is_active' => true
                ]
            ],
            [ /* Data set #3. Save without Frontend Name and with Description. */
                [
                    'name' => null,
                    'description' => 'zzz1',
                    'is_active' => false
                ],
                'eu-3',
                [
                    'name' => 'EU-source-3',
                    'description' => 'zzz1',
                    'is_active' => false
                ]
            ]
        ];
    }
}
