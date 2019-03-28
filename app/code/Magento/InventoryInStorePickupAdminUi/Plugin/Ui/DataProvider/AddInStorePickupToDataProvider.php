<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Plugin\Ui\DataProvider;

use Magento\InventoryAdminUi\Ui\DataProvider\SourceDataProvider;
use Magento\InventoryInStorePickupApi\Api\Data\InStorePickupInterface;

class AddInStorePickupToDataProvider
{
    /**
     * Convert the extension attribute boolean (true|false) to string integer value ("1"|"0") to match expected type.
     * If we pass (true|false) to UI Component for it to be recognised the return value will end up being string
     * ("true"|"false") which evaluates to (true), therefore adjust value to match other non extension attribute values.
     *
     * @param SourceDataProvider $subject
     * @param array $result
     *
     * @return array
     */
    public function afterGetData(
        SourceDataProvider $subject,
        array $result
    ): array {
        if (array_key_exists('items', $result)) {
            foreach ($result['items'] as $key => $item) {
                $result['items'][$key] = $this->convertExtensionAttributeBooleanToIntStr($item);
            }
        } else {
            // Single attribute returned in:
            // \Magento\InventoryAdminUi\Ui\DataProvider\SourceDataProvider::getData
            foreach ($result as $key => $item) {
                $result[$key]['general'] = $this->convertExtensionAttributeBooleanToIntStr($item['general']);
            }
        }

        return $result;
    }

    /**
     * Convert the extension attribute boolean (true|false) to string integer value ("1"|"0") to match expected type.
     *
     * @param array $item
     *
     * @return array
     */
    private function convertExtensionAttributeBooleanToIntStr(array $item):array {
        if (isset($item[InStorePickupInterface::EXTENSION_ATTRIBUTES_KEY]) &&
            isset($item[InStorePickupInterface::EXTENSION_ATTRIBUTES_KEY][InStorePickupInterface::IN_STORE_PICKUP_CODE])
        ) {
            $item[InStorePickupInterface::EXTENSION_ATTRIBUTES_KEY][InStorePickupInterface::IN_STORE_PICKUP_CODE] =
                (string)(int)$item[InStorePickupInterface::EXTENSION_ATTRIBUTES_KEY][InStorePickupInterface::IN_STORE_PICKUP_CODE];
        }

        return $item;
    }
}
