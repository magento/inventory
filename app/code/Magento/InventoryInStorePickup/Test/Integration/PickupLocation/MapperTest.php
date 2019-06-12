<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration\PickupLocation;

use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationExtensionInterface;
use Magento\InventoryInStorePickupApi\Model\Mapper;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var string
     */
    private $sourceCode;

    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->sourceRepository = $this->objectManager->create(SourceRepositoryInterface::class);
        $this->sourceCode = 'pickup';
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/pickup_location.php
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Wrong mapping provided for Magento\InventoryApi\Api\Data\SourceInterface. Field
     *     'source_fail_field' is not found.
     */
    public function testWrongMappingForSource()
    {
        $source = $this->sourceRepository->get($this->sourceCode);
        $map = $this->getMap();
        $map['source_fail_field'] = 'fail_field';
        /** @var  Mapper $mapper */
        $mapper = $this->objectManager->create(Mapper::class, ['map' => $map]);
        $mapper->map($source);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/pickup_location.php
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Wrong mapping provided for
     *     Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface. Field 'extension_attributes.fail_field'
     *     is not found.
     */
    public function testWrongMappingForPickupLocationExtensionAttributes()
    {
        $source = $this->sourceRepository->get($this->sourceCode);
        $map = $this->getMap();
        $map['name'] = 'extension_attributes.fail_field';
        /** @var  Mapper $mapper */
        $mapper = $this->objectManager->create(Mapper::class, ['map' => $map]);
        $mapper->map($source);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/pickup_location.php
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Wrong mapping provided for
     *     Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface. Field 'fail_field' is not found.
     */
    public function testWrongMappingForPickupLocation()
    {
        $source = $this->sourceRepository->get($this->sourceCode);
        $map = $this->getMap();
        $map['name'] = 'fail_field';
        /** @var  Mapper $mapper */
        $mapper = $this->objectManager->create(Mapper::class, ['map' => $map]);
        $mapper->map($source);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/pickup_location.php
     */
    public function testMapPickupLocation()
    {
        $source = $this->sourceRepository->get($this->sourceCode);
        /** @var  Mapper $mapper */
        $mapper = $this->objectManager->create(Mapper::class);
        $pickupLocation = $mapper->map($source);

        $this->assertEquals($source->getSourceCode(), $pickupLocation->getSourceCode());
        $this->assertEquals($source->getExtensionAttributes()->getFrontendName(), $pickupLocation->getName());
        $this->assertNotEquals($source->getDescription(), $pickupLocation->getDescription());
        $this->assertEquals($source->getEmail(), $pickupLocation->getEmail());
        $this->assertEquals($source->getContactName(), $pickupLocation->getContactName());
        $this->assertEquals(
            $source->getExtensionAttributes()->getFrontendDescription(),
            $pickupLocation->getDescription()
        );
        $this->assertNotEquals($source->getName(), $pickupLocation->getName());
        $this->assertEquals($source->getLatitude(), $pickupLocation->getLatitude());
        $this->assertEquals($source->getLongitude(), $pickupLocation->getLongitude());
        $this->assertEquals($source->getCountryId(), $pickupLocation->getCountryId());
        $this->assertEquals($source->getRegionId(), $pickupLocation->getRegionId());
        $this->assertEquals($source->getRegion(), $pickupLocation->getRegion());
        $this->assertEquals($source->getCity(), $pickupLocation->getCity());
        $this->assertEquals($source->getStreet(), $pickupLocation->getStreet());
        $this->assertEquals($source->getPostcode(), $pickupLocation->getPostcode());
        $this->assertEquals($source->getPhone(), $pickupLocation->getPhone());
        $this->assertEquals($source->getFax(), $pickupLocation->getFax());
        $this->assertInstanceOf(PickupLocationExtensionInterface::class, $pickupLocation->getExtensionAttributes());
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/pickup_location.php
     * @throws
     */
    public function testEmptyFrontendName()
    {
        $source = $this->sourceRepository->get($this->sourceCode);
        $source->getExtensionAttributes()->setFrontendName(null);

        /** @var  Mapper $mapper */
        $mapper = $this->objectManager->create(Mapper::class);

        $pickupLocation = $mapper->map($source);
        $this->assertEquals($source->getName(), $pickupLocation->getName());
    }

    /**
     * @return array
     */
    private function getMap(): array
    {
        return [
            'source_code' => 'source_code',
            'email' => 'email',
            'fax' => 'fax',
            'contact_name' => 'contact_name',
            'description' => 'description',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'country_id' => 'country_id',
            'region_id' => 'region_id',
            'region' => 'region',
            'city' => 'city',
            'street' => 'street',
            'postcode' => 'postcode',
            'phone' => 'phone'
        ];
    }
}
