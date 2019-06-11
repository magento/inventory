<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote\AddressCollection;

use Magento\Quote\Api\Data\AddressExtensionInterfaceFactory;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\ResourceModel\Quote\Address\Collection;

/**
 * Load Pickup Location and add to Extension Attributes.
 */
class GetPickupLocationInformationPlugin
{
    private const PICKUP_LOCATION_CODE = 'pickup_location_code';
    private const TABLE_ALIAS = 'iplqa';

    /**
     * @var AddressExtensionInterfaceFactory
     */
    private $addressExtensionInterfaceFactory;

    /**
     * GetPickupLocationInformation constructor.
     *
     * @param AddressExtensionInterfaceFactory $addressExtensionInterfaceFactory
     */
    public function __construct(AddressExtensionInterfaceFactory $addressExtensionInterfaceFactory)
    {
        $this->addressExtensionInterfaceFactory = $addressExtensionInterfaceFactory;
    }

    /**
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

        if (!isset($collection->getSelect()->getPart(\Zend_Db_Select::FROM)[self::TABLE_ALIAS])) {
            $collection->getSelect()->joinLeft(
                [self::TABLE_ALIAS => 'inventory_pickup_location_quote_address'],
                self::TABLE_ALIAS . '.address_id = main_table.address_id',
                [self::PICKUP_LOCATION_CODE]
            );
        }

        $result = $proceed($printQuery, $logQuery);

        /** @var Address $address */
        foreach ($collection->getItems() as $address) {
            if ($address->hasData(self::PICKUP_LOCATION_CODE)) {
                $this->addPickupLocationToExtensionAttributes($address);
            }
            $address->unsetData(self::PICKUP_LOCATION_CODE);
        }

        return $result;
    }

    /**
     * Add Loaded Pickup Location to Extension Attributes.
     *
     * @param Address $item
     */
    private function addPickupLocationToExtensionAttributes(Address $item): void
    {
        if (!$item->getExtensionAttributes()) {
            $item->setExtensionAttributes($this->addressExtensionInterfaceFactory->create());
        }

        $item->getExtensionAttributes()->setPickupLocationCode($item->getData('pickup_location_code'));
    }
}
