<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryDistanceBasedSourceSelectionApi\Api;

/**
 * Get selected distance provider code
 *
 * @api
 */
interface GetDistanceProviderCodeInterface
{
    /**
     * Get Default distance provider code
     *
     * @return string
     */
    public function execute(): string;
}
