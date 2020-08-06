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
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AreaInterface;

/**
 * Get Distance to Sources.
 *
 * Get Source Codes and distance to them, ordered by distance to request coordinates using
 * Haversine formula (Great Circle Distance) database query.
 */
class GetOrderedDistanceToSources
{
    private const EARTH_RADIUS_KM = 6372.797;
    private const CHUNK = 30;

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
     * @param LatLngInterface[] $latsLngs
     * @param int $radius
     *
     * @return float[]
     */
    public function execute(array $latsLngs, int $radius): array
    {
        $connection = $this->resourceConnection->getConnection();
        $sourceTable = $this->resourceConnection->getTableName('inventory_source');
        $latsLngsChunks = array_chunk($latsLngs, self::CHUNK);
        $distancesChunks = [[]];
        foreach ($latsLngsChunks as $latsLngsChunk) {
            $query = $connection->select()
                ->from($sourceTable)
                ->where(SourceInterface::ENABLED)
                ->reset(Select::COLUMNS)
                ->columns($this->createDistanceColumns($latsLngsChunk));
            $query = $this->processHavingClause($query, $radius, $latsLngsChunk);
            $distancesChunks[] = $connection->fetchPairs($query);
        }
        $distances = [];
        foreach (array_reverse($distancesChunks) as $distanceChunk) {
            $distances = $distances + $distanceChunk;
        }

        return array_map('floatval', $distances);
    }

    /**
     * Build having clause.
     *
     * @param Select $query
     * @param int $radius
     * @param array $latLngs
     * @return Select
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    private function processHavingClause(Select $query, int $radius, array $latLngs): Select
    {
        foreach ($latLngs as $key => $latLng) {
            $query->orHaving(AreaInterface::DISTANCE_FIELD . $key . ' <= ?', $radius);
        }

        return $query;
    }

    /**
     * Construct DB query to calculate Great Circle Distance.
     *
     * @param LatLngInterface[] $latLngs
     *
     * @return array
     */
    private function createDistanceColumns(array $latLngs): array
    {
        $result = ['source_code'];
        foreach ($latLngs as $key => $latLng) {
            $result[] = '(' . self::EARTH_RADIUS_KM . ' * ACOS('
                . 'COS(RADIANS(' . $latLng->getLat() . ')) * '
                . 'COS(RADIANS(latitude)) * '
                . 'COS(RADIANS(longitude) - RADIANS(' . $latLng->getLng() . ')) + '
                . 'SIN(RADIANS(' . $latLng->getLat() . ')) * '
                . 'SIN(RADIANS(latitude))'
                . '))' . ' AS ' . AreaInterface::DISTANCE_FIELD . $key;
        }

        return $result;
    }
}
