<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Block\Adminhtml\Order\View;

use Magento\InventoryInStorePickupAdminUi\Controller\Adminhtml\Order\NotifyPickup;
use Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface;

/**
 * TODO: is it possible to replace with UI Component?
 *
 * @package Magento\InventoryInStorePickupAdminUi\Block\Adminhtml\Order\View
 */
class ReadyForPickup extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Block group
     *
     * @var string
     */
    protected $_blockGroup = 'Magento_Sales';

    /**
     * @var \Magento\Sales\Block\Adminhtml\Order\View
     */
    private $viewBlock;

    /**
     * @var \Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface
     */
    private $isReadyForPickup;

    /**
     * ReadyForPickup constructor.
     *
     * @param \Magento\Backend\Block\Widget\Context                                 $context
     * @param \Magento\Sales\Block\Adminhtml\Order\View                             $viewBlock
     * @param \Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface $isReadyForPickup
     * @param array                                                                 $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Sales\Block\Adminhtml\Order\View $viewBlock,
        IsOrderReadyForPickupInterface $isReadyForPickup,
        array $data = []
    ) {
        $this->viewBlock = $viewBlock;
        $this->isReadyForPickup = $isReadyForPickup;

        parent::__construct($context, $data);
    }

    /**
     *  Rendering Ready for Pickup button
     */
    protected function _construct()
    {
        $this->_objectId = 'order_id';
        $this->_controller = 'adminhtml_order';
        $this->_mode = 'view';

        /* TODO: always display but throw warnings? */
        if ($this->isDisplayButton()) {
            $message = __(
                'Are you sure you want to notify the customer that order is ready for pickup and create shipment?'
            );
            $this->addButton(
                'ready_for_pickup',
                [
                    'label'   => __('Notify Order is Ready for Pickup'),
                    'class'   => 'action-default ready-for-pickup',
                    'onclick' => sprintf(
                        "confirmSetLocation('%s', '%s')",
                        $message,
                        $this->viewBlock->getUrl('sales/*/notifyPickup')
                    )
                ]
            );
        }
    }

    /**
     * @return bool
     */
    private function isEmailsSendingAllowed():bool
    {
        return $this->_authorization->isAllowed(NotifyPickup::ADMIN_RESOURCE);
    }

    /**
     * @return bool
     */
    private function isDisplayButton():bool
    {
        return $this->isEmailsSendingAllowed()
            && $this->isReadyForPickup->execute((int)$this->viewBlock->getOrderId());
    }
}
