<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesAdminUi\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\InventoryInStorePickupSalesAdminUi\Controller\Adminhtml\Order\NotifyPickup;
use Magento\InventoryInStorePickupSalesAdminUi\Model\IsRenderReadyForPickupButton;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Block\Adminhtml\Order\View;

/**
 * Render 'Notify Order is Ready for Pickup' button on order view page.
 * @api
 */
class ReadyForPickupButton implements ButtonProviderInterface
{
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
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var View
     */
    private $viewBlock;

    /**
     * @var ButtonList
     */
    private $buttonList;

    /**
     * @param IsRenderReadyForPickupButton $isDisplayButton
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AuthorizationInterface $authorization
     * @param View $viewBlock
     * @param ButtonList $buttonList
     */
    public function __construct(
        IsRenderReadyForPickupButton $isDisplayButton,
        ShipmentRepositoryInterface $shipmentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AuthorizationInterface $authorization,
        View $viewBlock,
        ButtonList $buttonList
    ) {
        $this->isDisplayButton = $isDisplayButton;
        $this->shipmentRepository = $shipmentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->authorization = $authorization;
        $this->viewBlock = $viewBlock;
        $this->buttonList = $buttonList;
    }

    /**
     * @inheritDoc
     */
    public function getButtonData()
    {
        if (!$this->isDisplayButton()) {
            return [];
        }

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('order_id', $this->viewBlock->getOrderId());
        $shipments = $this->shipmentRepository->getList($searchCriteria->create());
        $isShipmentCreated = $shipments->getTotalCount() > 0;
        $message = $isShipmentCreated
            ? __('Are you sure you want to notify the customer that order is ready for pickup?')
            : __('Are you sure you want to notify the customer that order is ready for pickup and create shipment?');
        $data = [
            'label' => __('Notify Order is Ready for Pickup'),
            'class' => 'action-default ready-for-pickup',
            'on_click' => sprintf(
                "confirmSetLocation('%s', '%s')",
                $message,
                $this->viewBlock->getUrl('sales/*/notifyPickup')
            ),
        ];
        $this->buttonList->remove('order_ship');

        return $data;
    }

    /**
     * Check if admin is allowed to send email.
     *
     * @return bool
     */
    private function isEmailsSendingAllowed(): bool
    {
        return $this->authorization->isAllowed(NotifyPickup::ADMIN_RESOURCE);
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
