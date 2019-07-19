<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\ResourceModel\Source;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;

/**
 * Get Source Codes ordered by distance
 *
 * Get Source Codes, ordered by distance to request coordinates using Haversine formula (Great Circle Distance)
 * database query.
 */
class GetDistanceOrderedSourceCodes
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
     * Get list of sources located in specified radius of specific coordinates.
     *
     * @param LatLngInterface $latLng
     * @param int $radius
     * @return string[]
     */
    public function execute(LatLngInterface $latLng, int $radius): array
    {
        $connection = $this->resourceConnection->getConnection();
        $sourceTable = $this->resourceConnection->getTableName('inventory_source');
        $query = $connection->select()
            ->from($sourceTable)
            ->where(SourceInterface::ENABLED)
            ->columns(['source_code', $this->createDistanceColumn($latLng) . ' AS distance'])
            ->having('distance <= ?', $radius)
            ->order('distance ASC');

        return $connection->fetchCol($query);
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
