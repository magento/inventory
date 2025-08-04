<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySourceSelection\Model;

use Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface;

class GetDefaultSourceSelectionAlgorithmCode implements GetDefaultSourceSelectionAlgorithmCodeInterface
{
    /**
     * @inheritdoc
     */
    public function execute(): string
    {
        return 'priority';
    }
}
