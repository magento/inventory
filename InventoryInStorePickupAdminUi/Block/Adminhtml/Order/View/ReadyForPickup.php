<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Block\Adminhtml\Order\View;

/**
 * TODO: is it possible to replace with UI Component?
 *
 * @package Magento\InventoryInStorePickupAdminUi\Block\Adminhtml\Order\View
 */
class ReadyForPickup extends \Magento\Backend\Block\Widget\Form\Container
{
    const ADMIN_SALES_EMAIL_RESOURCE = 'Magento_Sales::emails';

    /**
     * Block group
     *
     * @var string
     */
    protected $_blockGroup = 'Magento_Sales';

    /**
     *  Rendering Ready for Pickup button
     */
    protected function _construct()
    {
        $this->_objectId = 'order_id';
        $this->_controller = 'adminhtml_order';
        $this->_mode = 'view';

        if ($this->isEmailsSendingAllowed()) {/* TODO: add is order ready check */
            $message = __('Are you sure you want to notify the customer that order is ready for pickup?');
            $this->addButton(
                'ready_for_pickup',
                [
                    'label'   => __('Notify Order is Ready for Pickup'),
                    'class'   => 'action-default ready-for-pickup',
                    'onclick' => sprintf(
                        "confirmSetLocation('%s', '%s')",
                        $message,
                        $this->getUrl('*/*/notifyPickup')
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
        return $this->_authorization->isAllowed(self::ADMIN_SALES_EMAIL_RESOURCE);
    }
}
