<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * @api
 */
interface SourceItemConfigurationInterface extends ExtensibleDataInterface
{
    /**
     * Default source configuration path
     */
    const XML_PATH_NOTIFY_STOCK_QTY = 'cataloginventory/item_options/notify_stock_qty';
    const XML_PATH_BACKORDERS = 'cataloginventory/item_options/backorders';

    const BACKORDERS_NO = 0;
    const BACKORDERS_YES_NONOTIFY = 1;
    const BACKORDERS_YES_NOTIFY = 2;

    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const BACKORDERS = 'backorders';
    const NOTIFY_STOCK_QTY = 'notify_stock_qty';
    /**#@-*/

    /**
     * @return int|null
     */
    public function getBackorders(): ?int;

    /**
     * @return float|null
     */
    public function getNotifyStockQty(): ?float;

    /**
     * Retrieve existing extension attributes object
     *
     * Null for return is specified for proper work SOAP requests parser
     *
     * @return \Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationExtensionInterface|null
     */
    public function getExtensionAttributes();
}
