<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote\AddressCollection;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\Quote\Api\Data\AddressExtensionInterfaceFactory;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\ResourceModel\Quote\Address\Collection;

/**
 * Load Pickup Location and add to Extension Attributes.
 */
class GetPickupLocationInformationPlugin
{
    private const TABLE_ALIAS = 'iplqa';

    /**
     * @var AddressExtensionInterfaceFactory
     */
    private $addressExtensionInterfaceFactory;

    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @param AddressExtensionInterfaceFactory $addressExtensionInterfaceFactory
     * @param ResourceConnection $connection
     */
    public function __construct(
        AddressExtensionInterfaceFactory $addressExtensionInterfaceFactory,
        ResourceConnection $connection
    ) {
        $this->addressExtensionInterfaceFactory = $addressExtensionInterfaceFactory;
        $this->connection = $connection;
    }

    /**
     * Load information about Pickup Location Code to collection of Quote Address.
     *
     * @param Collection $collection
     * @param \Closure $proceed
     * @param bool $printQuery
     * @param bool $logQuery
     *
     * @return Collection
     * @throws \Zend_Db_Select_Exception
     */
    public function aroundLoadWithFilter(
        Collection $collection,
        \Closure $proceed,
        bool $printQuery,
        bool $logQuery
    ): Collection {
        if ($collection->isLoaded()) {
            return $proceed($printQuery, $logQuery);
        }

        if (!isset($collection->getSelect()->getPart(Select::FROM)[self::TABLE_ALIAS])) {
            $table = $this->connection->getTableName('inventory_pickup_location_quote_address', 'checkout');
            $collection->getSelect()->joinLeft(
                [self::TABLE_ALIAS => $table],
                self::TABLE_ALIAS . '.address_id = main_table.address_id',
                [PickupLocationInterface::PICKUP_LOCATION_CODE]
            );
        }

        $result = $proceed($printQuery, $logQuery);

        foreach ($collection as $address) {
            $this->processAddress($address);
        }

        return $result;
    }

    /**
     * Process address entity.
     *
     * @param Address $address
     *
     * @return void
     */
    private function processAddress(Address $address): void
    {
        $hasDataChanges = $address->hasDataChanges();
        if ($address->getData(PickupLocationInterface::PICKUP_LOCATION_CODE)) {
            $this->addPickupLocationToExtensionAttributes($address);
        }
        $address->unsetData(PickupLocationInterface::PICKUP_LOCATION_CODE);
        $address->setDataChanges($hasDataChanges);
    }

    /**
     * Add Loaded Pickup Location to Extension Attributes.
     *
     * @param Address $item
     *
     * @return void
     */
    private function addPickupLocationToExtensionAttributes(Address $item): void
    {
        if (!$item->getExtensionAttributes()) {
            $item->setExtensionAttributes($this->addressExtensionInterfaceFactory->create());
        }

        $item->getExtensionAttributes()->setPickupLocationCode(
            $item->getData(PickupLocationInterface::PICKUP_LOCATION_CODE)
        );
    }
}
