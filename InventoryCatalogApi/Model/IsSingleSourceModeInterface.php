<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

/**
 * Service checks if the system has multiple sources configured and enabled
 *
 * @api
 */
interface IsSingleSourceModeInterface
{
    /**
     * Check if system has more than one enabled Source configured
     *
     * @return bool
     */
    public function execute(): bool;
}
