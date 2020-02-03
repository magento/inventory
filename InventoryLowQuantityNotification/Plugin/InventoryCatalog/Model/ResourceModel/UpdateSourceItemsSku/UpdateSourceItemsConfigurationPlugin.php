<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Plugin\InventoryCatalog\Model\ResourceModel\UpdateSourceItemsSku;

use Magento\Inventory\Model\ResourceModel\SourceItem\UpdateMultiple as SourceUpdateMultiple;
use Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration\UpdateMultiple;

/**
 * Update source items configuration skus plugin.
 */
class UpdateSourceItemsConfigurationPlugin
{
    /**
     * @var UpdateMultiple
     */
    private $updateSourceConfigurationSku;

    /**
     * @param UpdateMultiple $updateSourceConfigurationSku
     */
    public function __construct(UpdateMultiple $updateSourceConfigurationSku)
    {
        $this->updateSourceConfigurationSku = $updateSourceConfigurationSku;
    }

    /**
     * Update source items configuration skus after source items skus have been updated.
     *
     * @param SourceUpdateMultiple $subject
     * @param void $result
     * @param string $oldSku
     * @param string $newSku
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(SourceUpdateMultiple $subject, $result, string $oldSku, string $newSku): void
    {
        $this->updateSourceConfigurationSku->execute($oldSku, $newSku);
    }
}
