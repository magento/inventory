<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Ui\Component\Listing\Column;

use Magento\InventoryInStorePickupApi\Api\Data\InStorePickupInterface;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Add grid column for in-store pickup. Prepare data
 */
class InStorePickup extends Column
{
    /**
     * Prepare data source by moving from extension_attributes to child of item so that it is accessible in listing grid.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource):array
    {
        if (isset($dataSource['data']['totalRecords'])
            && $dataSource['data']['totalRecords'] > 0
        ) {
            foreach ($dataSource['data']['items'] as &$row) {
                $row[InStorePickupInterface::IN_STORE_PICKUP_CODE] =
                    isset($row[InStorePickupInterface::EXTENSION_ATTRIBUTES_KEY][InStorePickupInterface::IN_STORE_PICKUP_CODE]) ?
                        $row[InStorePickupInterface::EXTENSION_ATTRIBUTES_KEY][InStorePickupInterface::IN_STORE_PICKUP_CODE] :
                        "";
            }
        }
        unset($row);

        return $dataSource;
    }
}