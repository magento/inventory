<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDistanceBasedSourceSelection\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryDistanceBasedSourceSelection\Model\Convert\AddressToString;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterface;

/**
 * Get available geonames data by address service.
 */
class GetGeoNamesDataByAddress
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var AddressToString
     */
    private $addressToString;

    /**
     * GetGeoNameDataByPostcode constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param AddressToString $addressToString
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        AddressToString $addressToString
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->addressToString = $addressToString;
    }

    /**
     * Return all available geonames information for given address.
     *
     * @param AddressInterface $address
     * @return array
     * @throws NoSuchEntityException
     */
    public function execute(AddressInterface $address): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_geoname');

        $qry = $connection->select()->from($tableName)
            ->where('country_code = ?', $address->getCountry())
            ->where('postcode = ?', $address->getPostcode())
            ->limit(1);

        $result[] = $connection->fetchRow($qry);
        if (!reset($result) && $address->getCity()) {
            $qry = $connection->select()->from($tableName)
                ->where('country_code = ?', $address->getCountry())
                ->where('city like ?', $address->getCity() . '%');

            $result = $connection->fetchAll($qry);
        }

        if (!$result || !reset($result)) {
            $qry = $connection->select()->from($tableName)
                ->where('country_code = ?', $address->getCountry())
                ->where('region = ?', $address->getRegion());

            $result = $connection->fetchAll($qry);
        }

        if (!$result || !reset($result)) {
            throw new NoSuchEntityException(
                __('Unknown geoname for %1', $this->addressToString->execute($address))
            );
        }

        return $result;
    }
}
