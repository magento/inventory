<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Get Pickup Location identifier by quote address identifier.
 */
class GetPickupLocationCodeByQuoteAddressId
{
    private const ADDRESS_ID = 'address_id';
    private const PICKUP_LOCATION_CODE = 'pickup_location_code';

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
     * Fetch pickup location identifier by quote address identifier.
     *
     * @param int $addressId
     *
     * @return string|null
     */
    public function execute(int $addressId): ?string
    {
        $connection = $this->connection->getConnection();
        $table = $this->connection->getTableName('inventory_pickup_location_quote_address');

        $select = $connection->select()
                             ->from($table, [self::PICKUP_LOCATION_CODE => self::PICKUP_LOCATION_CODE])
                             ->where(self::ADDRESS_ID . '= ?', $addressId)
                             ->limit(1);

        $id = $connection->fetchOne($select);

        return $id ?: null;
    }
}
