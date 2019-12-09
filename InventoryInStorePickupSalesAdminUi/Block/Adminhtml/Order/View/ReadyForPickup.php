<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesAdminUi\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryInStorePickupSalesAdminUi\Controller\Adminhtml\Order\NotifyPickup;
use Magento\InventoryInStorePickupSalesAdminUi\Model\IsRenderReadyForPickupButton;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Block\Adminhtml\Order\View;

/**
 * TODO: is it possible to replace with UI Component?
 * @api
 * @see https://github.com/magento-engcom/msi/issues/2161
 *
 * Render 'Notify Order is Ready for Pickup' button on order view page
 */
class ReadyForPickup extends Container
{
    /**
     * @inheritdoc
     */
    protected $_blockGroup = 'Magento_Sales';

    /**
     * @var View
     */
    private $viewBlock;

    /**
     * @var IsRenderReadyForPickupButton
     */
    private $isDisplayButton;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param Context $context
     * @param View $viewBlock
     * @param IsRenderReadyForPickupButton $isDisplayButton
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        View $viewBlock,
        IsRenderReadyForPickupButton $isDisplayButton,
        ShipmentRepositoryInterface $shipmentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        $this->viewBlock = $viewBlock;
        $this->isDisplayButton = $isDisplayButton;
        $this->shipmentRepository = $shipmentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;

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

        if (!$this->isDisplayButton()) {
            return;
        }

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('order_id', $this->viewBlock->getOrderId());
        $shipments = $this->shipmentRepository->getList($searchCriteria->create());
        $isShipmentCreated = $shipments->getTotalCount() > 0;

        $message = $isShipmentCreated
            ? __('Are you sure you want to notify the customer that order is ready for pickup?')
            : __('Are you sure you want to notify the customer that order is ready for pickup and create shipment?');
        $this->addButton(
            'ready_for_pickup',
            [
                'label' => __('Notify Order is Ready for Pickup'),
                'class' => 'action-default ready-for-pickup',
                'onclick' => sprintf(
                    "confirmSetLocation('%s', '%s')",
                    $message,
                    $this->viewBlock->getUrl('sales/*/notifyPickup')
                ),
            ]
        );
        $this->buttonList->remove('order_ship');
    }

    /**
     * Check if admin is allowed to send email.
     *
     * @return bool
     */
    private function isEmailsSendingAllowed(): bool
    {
        return $this->_authorization->isAllowed(NotifyPickup::ADMIN_RESOURCE);
    }

    /**
     * Check if delivery notification button may be displayed.
     *
     * @return bool
     */
    private function isDisplayButton(): bool
    {
        return $this->isEmailsSendingAllowed()
            && $this->isDisplayButton->execute($this->viewBlock->getOrder());
    }
}
