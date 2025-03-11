<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Test\Unit\Plugin\Quote\AddressCollection;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupQuote\Plugin\Quote\AddressCollection\GetPickupLocationInformationPlugin;
use Magento\Quote\Api\Data\AddressExtensionInterfaceFactory;
use Magento\Quote\Model\ResourceModel\Quote\Address\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Provide tests for GetPickupLocationInformationPlugin.
 */
class GetPickupLocationInformationPluginTest extends TestCase
{
    /**
     * Test subject.
     *
     * @var GetPickupLocationInformationPlugin
     */
    private $getPickupLocationInformationPlugin;

    /**
     * @var AddressExtensionInterfaceFactory
     */
    private $addressExtensionInterfaceFactory;

    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->addressExtensionInterfaceFactory = $this->getMockBuilder(AddressExtensionInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();
        $this->connection = $this->getMockBuilder(ResourceConnection::class)
            ->onlyMethods(['getTableName'])
            ->addMethods(['getSelect'])
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->getPickupLocationInformationPlugin = $objectManager->getObject(
            GetPickupLocationInformationPlugin::class,
            [
                'addressExtensionInterfaceFactory' => $this->addressExtensionInterfaceFactory,
                'connection' => $this->connection,
            ]
        );
    }

    /**
     * Verify table alias considering table prefix for 'inventory_pickup_location_quote_address' table.
     *
     * @return void
     * @throws \Zend_Db_Select_Exception
     */
    public function testExecute(): void
    {
        $tableName = 'inventory_pickup_location_quote_address';
        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $select->expects(self::once())
            ->method('getPart')
            ->with(Select::FROM)
            ->willReturn([]);
        $select->expects(self::once())
            ->method('joinLeft')
            ->with(
                ['iplqa' => $tableName],
                'iplqa' . '.address_id = main_table.address_id',
                [PickupLocationInterface::PICKUP_LOCATION_CODE]
            )->willReturnSelf();
        /** @var Collection|MockObject $collection */
        $collection = $this->getMockBuilder(Collection::class)
            ->onlyMethods(['getSelect', 'isLoaded'])
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects(self::exactly(2))
            ->method('isLoaded')
            ->willReturnOnConsecutiveCalls(false, true);
        $collection->expects(self::exactly(2))
            ->method('getSelect')
            ->willReturn($select);
        $proceed = function () use ($collection) {
            return $collection;
        };
        $this->connection->expects(self::once())
            ->method('getTableName')
            ->with('' . $tableName . '', 'checkout')
            ->willReturn($tableName);

        $result = $this->getPickupLocationInformationPlugin->aroundLoadWithFilter($collection, $proceed, false, false);
        self::assertInstanceOf(Collection::class, $result);
    }
}
