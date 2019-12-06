<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Plugin\Sales\ShipOrder;

use Magento\InventoryInStorePickupSalesApi\Model\IsStorePickupOrderInterface;
use Magento\Sales\Api\Data\ShipmentCommentCreationInterface;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Exception\CouldNotShipException;

/**
 * Process shipment for store pickup delivery method.
 */
class AdaptShipOrderPlugin
{
    /**
     * @var IsStorePickupOrderInterface
     */
    private $isOrderStorePickup;

    /**
     * @param IsStorePickupOrderInterface $isOrderStorePickup
     */
    public function __construct(
        IsStorePickupOrderInterface $isOrderStorePickup
    ) {
        $this->isOrderStorePickup = $isOrderStorePickup;
    }

    /**
     * Restrict shipment creation in case order delivery method is "in store pickup".
     *
     * @see \Magento\InventoryInStorePickupSalesApi\Api\NotifyOrdersAreReadyForPickupInterface
     *
     * @param ShipOrderInterface $subject
     * @param $orderId
     * @param array $items
     * @param bool $notify
     * @param bool $appendComment
     * @param ShipmentCommentCreationInterface|null $comment
     * @param array $tracks
     * @param array $packages
     * @param ShipmentCreationArgumentsInterface|null $arguments
     * @return void
     * @throws CouldNotShipException
     */
    public function beforeExecute(
        ShipOrderInterface $subject,
        $orderId,
        array $items = [],
        $notify = false,
        $appendComment = false,
        ShipmentCommentCreationInterface $comment = null,
        array $tracks = [],
        array $packages = [],
        ShipmentCreationArgumentsInterface $arguments = null
    ): void {
        $sourceCode = $arguments === null ? $arguments : $arguments->getExtensionAttributes()->getSourceCode();
        if ($this->isOrderStorePickup->execute((int)$orderId) && $sourceCode === null) {
            throw new CouldNotShipException(
                __(
                    'Not able to ship order with \'In Store Pick-Up\' delivery method.'
                    . ' Order should be notified as \'Ready For Pick-Up\' instead.'
                )
            );
        }
    }
}
