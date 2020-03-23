<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

/**
 * Save Quote Address Pickup Location by Address Id.
 */
class SaveQuoteAddressPickupLocation
{
    private const ADDRESS_ID = 'address_id';

    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @param ResourceConnection $connection
     */
    public function __construct(
        ResourceConnection $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * Fetch pickup location identifier by order identifier.
     *
     * @param int $addressId
     * @param string $pickupLocationCode
     *
     * @return void
     */
    public function execute(int $addressId, string $pickupLocationCode): void
    {
        $connection = $this->connection->getConnection('checkout');
        $table = $this->connection->getTableName('inventory_pickup_location_quote_address', 'checkout');

        $data = [
            self::ADDRESS_ID => $addressId,
            PickupLocationInterface::PICKUP_LOCATION_CODE => $pickupLocationCode
        ];

        $connection->insertOnDuplicate($table, $data);
    }
}
