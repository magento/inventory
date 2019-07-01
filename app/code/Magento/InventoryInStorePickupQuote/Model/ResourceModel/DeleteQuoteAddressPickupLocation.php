<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Delete assignment of quote address to Pickup Location.
 */
class DeleteQuoteAddressPickupLocation
{
    private const ADDRESS_ID = 'address_id';

    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @param ResourceConnection $connection
     */
    public function __construct(ResourceConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Delete assignment of quote address to Pickup Location.
     *
     * @param int $addressId
     *
     * @return void
     */
    public function execute(int $addressId): void
    {
        $connection = $this->connection->getConnection();
        $table = $this->connection->getTableName('inventory_pickup_location_quote_address');

        $connection->delete($table, [self::ADDRESS_ID => $addressId]);
    }
}
