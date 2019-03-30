<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Plugin\Ui\DataProvider;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\InventoryAdminUi\Ui\DataProvider\SourceDataProvider;

class ConvertBooleanToStringPlugin
{
    /**
     * Convert the extension attribute boolean (true|false) to string integer value ("1"|"0") to match expected type.
     * Ui DataProvider does not support this for Extension Attributes   .
     * @see \Magento\Ui\DataProvider\SearchResultFactory::createAttributes
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
                $result['items'][$key] = $this->convertBooleanToString($item);
            }
        } else {
            // Single attribute returned in:
            // \Magento\InventoryAdminUi\Ui\DataProvider\SourceDataProvider::getData
            foreach ($result as $key => $item) {
                $result[$key]['general'] = $this->convertBooleanToString($item['general']);
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
    private function convertBooleanToString(array $item):array
    {
        if (isset($item[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY])) {
            foreach ($item[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY] as $code => $value) {
                if (is_bool($value)) {
                    $item[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY][$code] = (string)(int)$value;
                }
            }
        }

        return $item;
    }
}
