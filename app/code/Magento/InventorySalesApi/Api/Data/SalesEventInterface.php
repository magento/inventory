<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Api\Data;

/**
 * Represents the sales event that brings to appending reservations.
 *
 * @api
 */
interface SalesEventInterface
{
    /**#@+
     * Constants for event types
     */
    public const EVENT_ORDER_PLACED = 'order_placed';
    public const EVENT_ORDER_CANCELED = 'order_canceled';
    public const EVENT_SHIPMENT_CREATED = 'shipment_created';
    public const EVENT_CREDITMEMO_CREATED = 'creditmemo_created';
    public const EVENT_INVOICE_CREATED = 'invoice_created';
    /**#@-*/

    /**#@+
     * Constants for event object types
     */
    public const OBJECT_TYPE_ORDER = 'order';
    /**#@-*/

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return string
     */
    public function getObjectType(): string;

    /**
     * @return string
     */
    public function getObjectId(): string;
}
