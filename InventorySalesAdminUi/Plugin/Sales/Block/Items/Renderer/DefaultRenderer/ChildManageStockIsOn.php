<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Plugin\Sales\Block\Items\Renderer\DefaultRenderer;

use Magento\Sales\Block\Adminhtml\Items\Renderer\DefaultRenderer;
use Magento\Sales\Model\Order\Creditmemo\Item;
use Magento\InventorySalesAdminUi\Model\GetIsManageStockForProduct;

/**
 * Manage Stock configuration of child product should override configuration of parent configurable product
 */
class ChildManageStockIsOn
{
    /**
     * @var GetIsManageStockForProduct
     */
    private $getIsManageStockForProduct;

    /**
     * @var string
     */
    private $configurableTypeCode = 'configurable';

    /**
     * @param GetIsManageStockForProduct $getIsManageStockForProduct
     */
    public function __construct(
        GetIsManageStockForProduct $getIsManageStockForProduct
    ) {
        $this->getIsManageStockForProduct = $getIsManageStockForProduct;
    }

    /**
     * Manage Stock configuration of child product should override configuration of parent configurable product
     *
     * @param DefaultRenderer $subject
     * @param bool|mixed $result
     * @param Item $item
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterCanReturnItemToStock(
        DefaultRenderer $subject,
        $result,
        Item $item
    ) {
        $orderItem = $item->getOrderItem();
        if ($orderItem->getProductType() !== $this->configurableTypeCode || $orderItem->getParentItem()) {
            return $result;
        }
        $isManageStock = $this->getIsManageStockForProduct->execute(
            $orderItem->getSku(),
            $orderItem->getStore()->getWebsite()->getCode()
        );
        if ($isManageStock !== null) {
            return $isManageStock;
        }
        return $result;
    }
}
