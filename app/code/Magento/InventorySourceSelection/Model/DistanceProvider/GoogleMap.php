<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySourceSelection\Model\DistanceProvider;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressRequestInterface;
use Magento\InventorySourceSelectionApi\Model\DistanceProviderInterface;

/**
 * @inheritdoc
 */
class GoogleMap implements DistanceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function execute(SourceInterface $source, AddressRequestInterface $destination): float
    {
        return 0;
    }
}
