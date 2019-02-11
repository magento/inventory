<?php
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\DistanceProvider\Offline;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\GetNearbySourcesFromPostcodeInterface;

class GetNearbySourcesFromPostcode implements GetNearbySourcesFromPostcodeInterface
{
    /** @var int  */
    private const EARTH_RADIUS_KM = 6372.797;
    /** @var ResourceConnection  */
    private $resourceConnection;

    private $sourceInterfaceFactory;

    public function __construct(ResourceConnection $resourceConnection,
                                SourceInterfaceFactory $sourceInterfaceFactory)
    {
        $this->resourceConnection = $resourceConnection;
        $this->sourceInterfaceFactory = $sourceInterfaceFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $country, string $postcode, int $radius)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_geoname');
        $sourceTable = $this->resourceConnection->getTableName('inventory_source');

        $qry = $connection->select()->from($tableName)
            ->where('country_code = ?', $country)
            ->where('postcode = ?', $postcode)
            ->limit(1);
        $row = $connection->fetchRow($qry);
        if(!$row){
            throw new NoSuchEntityException(
                __('Unknown geoname for %1 in %2', $postcode, $country)
            );
        }

        // Still here so the target zipcode is valid
        $lat = (float)$row['latitude'];
        $lng = (float)$row['longitude'];

        // Build up a radial query
        $qry = $connection->select()
            ->from($sourceTable)
            ->columns(['*', $this->_createDistanceColumn($lat, $lng) . ' AS distance'])
            ->having('distance <= ?', $radius);

        $rows = $connection->fetchAll($qry);
        $results = [];
        foreach($rows as $row){
            $item = $this->sourceInterfaceFactory->create(['data'=>$row]);
            $results[] = $item;
        };

        return $results;


    }

    private function _createDistanceColumn(float $fLatitude, float $fLongitude){
        return '(' . self::EARTH_RADIUS_KM . ' * ACOS('
            . 'COS(RADIANS(' . (float)$fLatitude . ')) * '
            . 'COS(RADIANS(latitude)) * '
            . 'COS(RADIANS(longitude) - RADIANS(' . (float)$fLongitude . ')) + '
            . 'SIN(RADIANS(' . (float)$fLatitude . ')) * '
            . 'SIN(RADIANS(latitude))'
            . '))';
    }
}