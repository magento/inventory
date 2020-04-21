<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model;

use InvalidArgumentException;
use Magento\Framework\App\ResourceConnection;

/**
 * Resolve website ID by store ID
 */
class StoreWebsiteResolver
{
    /**
     * @var array
     */
    private $storeWebsiteIds;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get website ID by store ID
     *
     * @param int $storeId store ID
     * @return int website ID
     * @throws InvalidArgumentException
     */
    public function execute(int $storeId): int
    {
        $websiteIds = $this->getWebsiteIds();
        if (!isset($websiteIds[$storeId])) {
            throw new InvalidArgumentException('Unable to resolve website ID for store ID ' . $storeId);
        }
        return $websiteIds[$storeId];
    }

    /**
     * Get storeIds with their websiteIds
     *
     * @return array
     */
    private function getWebsiteIds(): array
    {
        if ($this->storeWebsiteIds === null) {
            $this->storeWebsiteIds = [];
            $connection = $this->resourceConnection->getConnection();
            $storeTableName = $this->resourceConnection->getTableName('store');
            $query = $connection
                ->select()
                ->from(
                    ['main_table' => $storeTableName],
                    ['store_id', 'website_id']
                );
            foreach ($connection->fetchAll($query) as $store) {
                $this->storeWebsiteIds[(int) $store['store_id']] = (int) $store['website_id'];
            }
        }
        return $this->storeWebsiteIds;
    }
}
