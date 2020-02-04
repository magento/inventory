<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesAdminUi\Plugin\Sales\Block\Adminhtml\Order\Create\Shipping\Address;

use Magento\Framework\Data\Form;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Sales\Block\Adminhtml\Order\Create\Shipping\Address;

/**
 * Adapt shipping address form for store pickup orders plugin.
 */
class AdaptFormPlugin
{
    /**
     * @var array
     */
    private $allowedFormFields;

    /**
     * @param array $allowedFormFields
     */
    public function __construct(array $allowedFormFields = [])
    {
        $this->allowedFormFields = $allowedFormFields;
    }

    /**
     * Remove unwanted fields from shipping address form in case delivery method is 'store_pickup'.
     *
     * @param Address $subject
     * @param Form $form
     * @return Form
     */
    public function afterGetForm(Address $subject, Form $form): Form
    {
        $order = $subject->getRequest()->getPost('order', []);
        $shippingMethod = $order['shipping_method'] ?? '';
        if ($shippingMethod === InStorePickup::DELIVERY_METHOD) {
            foreach ($form->getElements() as $element) {
                foreach ($element->getElements() as $field) {
                    if (!in_array($field->getId(), $this->allowedFormFields)) {
                        $element->removeField($field->getId());
                    }
                }
            }
        }

        return $form;
    }
}
