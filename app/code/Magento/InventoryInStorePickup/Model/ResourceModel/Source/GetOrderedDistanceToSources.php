<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\ResourceModel\Source;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;

/**
 * Get Distance to Sources.
 *
 * Get Source Codes and distance to them, ordered by distance to request coordinates using
 * Haversine formula (Great Circle Distance) database query.
 */
class GetOrderedDistanceToSources
{
    private const EARTH_RADIUS_KM = 6372.797;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get associated list of source codes and distances to specific coordinates limited by specific radius.
     *
     * @param LatLngInterface $latLng
     * @param int $radius
     *
     * @return float[]
     */
    public function execute(LatLngInterface $latLng, int $radius): array
    {
        $connection = $this->resourceConnection->getConnection();
        $sourceTable = $this->resourceConnection->getTableName('inventory_source');
        $query = $connection->select()
                            ->from($sourceTable)
                            ->where(SourceInterface::ENABLED)
                            ->reset(Select::COLUMNS)
                            ->columns(
                                [
                                    'source_code',
                                    $this->createDistanceColumn(
                                        $latLng
                                    ) . ' AS ' . DistanceFilterInterface::DISTANCE_FIELD
                                ]
                            )
                            ->having(DistanceFilterInterface::DISTANCE_FIELD . ' <= ?', $radius)
                            ->order(DistanceFilterInterface::DISTANCE_FIELD . ' ASC');

        $distances = $connection->fetchPairs($query);

        return array_map('floatval', $distances);
    }

    /**
     * Construct DB query to calculate Great Circle Distance
     *
     * @param LatLngInterface $latLng
     *
     * @return string
     */
    private function createDistanceColumn(LatLngInterface $latLng): string
    {
        return '(' . self::EARTH_RADIUS_KM . ' * ACOS('
            . 'COS(RADIANS(' . $latLng->getLat() . ')) * '
            . 'COS(RADIANS(latitude)) * '
            . 'COS(RADIANS(longitude) - RADIANS(' . $latLng->getLng() . ')) + '
            . 'SIN(RADIANS(' . $latLng->getLat() . ')) * '
            . 'SIN(RADIANS(latitude))'
            . '))';
    }
}
