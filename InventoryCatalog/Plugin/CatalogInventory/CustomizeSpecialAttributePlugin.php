<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\AbstractModel;
use Magento\InventoryCatalog\Model\ProductStockStatus;

/**
 * Plugin to customize specific condition for product attributes
 */
class CustomizeSpecialAttributePlugin
{
    /**
     * @var ProductStockStatus
     */
    private $productStockStatus;

    /**
     * @param ProductStockStatus|null $productStock
     */
    public function __construct(
        ProductStockStatus $productStock = null
    ) {
        $this->productStockStatus = $productStock ?: ObjectManager::getInstance()->get(ProductStockStatus::class);
    }

    /**
     * Will filter product special attribute
     *
     * @param mixed $subject
     * @param callable $proceed
     * @param AbstractModel $model
     * @return mixed
     */
    public function aroundValidate(
        mixed $subject,
        callable $proceed,
        AbstractModel $model
    ) {
        if ('quantity_and_stock_status' == $subject->getAttribute()) {
            return $subject->validateAttribute($this->productStockStatus->isProductInStock(
                $model->getSku(),
                (int)$model->getStore()->getWebsiteId()
            ));
        }
        return $proceed($model);
    }

    /**
     * Will filter product special attribute
     *
     * @param mixed $subject
     * @param callable $proceed
     * @param Collection $model
     * @return mixed
     */
    public function aroundCollectValidatedAttributes(
        mixed $subject,
        callable $proceed,
        Collection $model
    ): mixed {
        if ('quantity_and_stock_status' == $subject->getAttribute()) {
            return $this;
        } else {
            return $proceed($model);
        }
    }
}
