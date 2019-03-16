<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\DistanceProvider\Offline;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\GetNearbySourcesByPostcodeInterface;

/**
 * Find nearest Inventory Sources by postal code using Haversine formula (Great Circle Distance) database query.
 */
class GetNearbySourcesByPostcode implements GetNearbySourcesByPostcodeInterface
{
    private const EARTH_RADIUS_KM = 6372.797;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var SourceInterfaceFactory
     */
    private $sourceInterfaceFactory;

    /**
     * @param ResourceConnection $resourceConnection
     * @param SourceInterfaceFactory $sourceInterfaceFactory
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        SourceInterfaceFactory $sourceInterfaceFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->sourceInterfaceFactory = $sourceInterfaceFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @throws
     */
    public function execute(string $country, string $postcode, int $radius): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_geoname');
        $sourceTable = $this->resourceConnection->getTableName('inventory_source');

        $query = $connection->select()->from($tableName)
            ->where('country_code = ?', $country)
            ->where('postcode = ?', $postcode)
            ->limit(1);
        $row = $connection->fetchRow($query);

        if (!$row) {
            throw new NoSuchEntityException(
                __('Unknown postcode %1 in %2', $postcode, $country)
            );
        }

        // Still here so the target postcode is valid
        $lat = (float)$row['latitude'];
        $lng = (float)$row['longitude'];

        // Build up a radial query
        $query = $connection->select()
            ->from($sourceTable)
            ->columns(['*', $this->createDistanceColumn($lat, $lng) . ' AS distance'])
            ->having('distance <= ?', $radius)
            ->order('distance ASC');

        $rows = $connection->fetchAll($query);
        $results = [];
        foreach ($rows as $row) {
            $item = $this->sourceInterfaceFactory->create(['data' => $row]);
            $results[] = $item;
        }

        return $results;
    }

    /**
     * Construct DB query to calculate Great Circle Distance
     *
     * @param float $latitude
     * @param float $longitude
     * @return string
     */
    private function createDistanceColumn(float $latitude, float $longitude)
    {
        return '(' . self::EARTH_RADIUS_KM . ' * ACOS('
            . 'COS(RADIANS(' . (float)$latitude . ')) * '
            . 'COS(RADIANS(latitude)) * '
            . 'COS(RADIANS(longitude) - RADIANS(' . (float)$longitude . ')) + '
            . 'SIN(RADIANS(' . (float)$latitude . ')) * '
            . 'SIN(RADIANS(latitude))'
            . '))';
    }
}
