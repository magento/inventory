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
 * Get Pickup Location identifier by quote address identifier.
 */
class GetPickupLocationCodeByQuoteAddressId
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
     * Fetch pickup location identifier by quote address identifier.
     *
     * @param int $addressId
     *
     * @return string|null
     */
    public function execute(int $addressId): ?string
    {
        $connection = $this->connection->getConnection('checkout');
        $table = $this->connection->getTableName('inventory_pickup_location_quote_address', 'checkout');

        $columns = [PickupLocationInterface::PICKUP_LOCATION_CODE => PickupLocationInterface::PICKUP_LOCATION_CODE];
        $select = $connection->select()
                             ->from($table, $columns)
                             ->where(self::ADDRESS_ID . '= ?', $addressId)
                             ->limit(1);

        $id = $connection->fetchOne($select);

        return $id ?: null;
    }
}
