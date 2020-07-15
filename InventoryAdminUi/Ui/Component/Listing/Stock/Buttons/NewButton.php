<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Ui\Component\Listing\Stock\Buttons;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * New Stock button configuration provider
 */
class NewButton implements ButtonProviderInterface
{
    /**
     * URL builder
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inheritdoc
     */
    public function getButtonData()
    {
        return [
            'on_click' => sprintf("location.href = '%s';", $this->urlBuilder->getUrl('*/*/new')),
            'class' => 'primary',
            'label' => __('Add New Stock'),
            'aclResource' => 'Magento_InventoryApi::stock_edit',
        ];
    }
}
