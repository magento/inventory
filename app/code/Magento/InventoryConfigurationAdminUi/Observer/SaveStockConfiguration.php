<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\InventoryConfigurationApi\Api\SetManageStockStatusConfigurationValueInterface;

class SaveStockConfiguration implements ObserverInterface
{
    /**
     * @var SetManageStockStatusConfigurationValueInterface
     */
    private $setManageStockStatusConfigurationValue;

    /**
     * @param SetManageStockStatusConfigurationValueInterface $setManageStockStatusConfigurationValue
     */
    public function __construct(
        SetManageStockStatusConfigurationValueInterface $setManageStockStatusConfigurationValue
    ) {
        $this->setManageStockStatusConfigurationValue = $setManageStockStatusConfigurationValue;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $stockId = (int)$observer->getStock()->getId();
        $request = $observer->getRequest();

        $configOptions = $request->getParam('inventory_configuration');
        if ($configOptions['manage_stock']['use_config_value']) {
            $this->setManageStockStatusConfigurationValue->forStock($stockId, null);
        } else {
            $this->setManageStockStatusConfigurationValue->forStock(
                $stockId,
                (int)$configOptions['manage_stock']['value']
            );
        }
    }
}
