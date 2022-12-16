<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAsyncOrder\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\InventorySales\Model\ReservationExecutionInterface;

/**
 *  Defer inventory reservation for async order or not.
 */
class ReservationExecution implements ReservationExecutionInterface
{
    /**
     * Config path for deferred stock update.
     */
    public const CONFIG_PATH_USE_DEFERRED_STOCK_UPDATE = 'cataloginventory/item_options/use_deferred_stock_update';

    /**
     * Constant for async option path.
     */
    private const ASYNC_ORDER_OPTION_PATH = 'checkout/async';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DeploymentConfig $deploymentConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * Do not defer placing inventory reservation when it is async order with no deferred stock update.
     *
     * @return bool
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function isDeferred(): bool
    {
        if ($this->deploymentConfig->get(self::ASYNC_ORDER_OPTION_PATH)
            && !$this->scopeConfig->isSetFlag(self::CONFIG_PATH_USE_DEFERRED_STOCK_UPDATE)) {
            return false;
        }

        return true;
    }
}
