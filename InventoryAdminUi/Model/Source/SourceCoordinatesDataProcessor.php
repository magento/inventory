<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Model\Source;

/**
 * Prepare source coordinates data (latitude and longitude). Specified for form structure
 */
class SourceCoordinatesDataProcessor
{
    /**
     * Normalizes latitude and longitude values in the input array, setting them to `null` if missing or empty.
     *
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        if (!isset($data['latitude']) || '' === $data['latitude']) {
            $data['latitude'] = null;
        }

        if (!isset($data['longitude']) || '' === $data['longitude']) {
            $data['longitude'] = null;
        }

        return $data;
    }
}
