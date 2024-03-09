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

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\Rule\Model\Condition\Product\AbstractProduct;

/**
 * Plugin to customize specific condition for product attributes
 */
class CustomizeSpecialAttributePlugin implements ResetAfterRequestInterface
{
    /**
     * @var array
     */
    private $stockStatus = [];

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->areProductsSalable = $areProductsSalable;
    }

    /**
     * Will filter product special attribute
     *
     * @param AbstractProduct $subject
     * @param callable $proceed
     * @param AbstractModel $model
     * @return mixed
     */
    public function aroundValidate(
        AbstractProduct $subject,
        callable $proceed,
        AbstractModel $model
    ) {
        if ('quantity_and_stock_status' == $subject->getAttribute()) {

            $websiteId = (int)$model->getStore()->getWebsiteId();
            $sku = $model->getSku();

            if (!isset($this->stockStatus[$websiteId][$sku])) {
                $result = $this->areProductsSalable->execute([$sku], $websiteId);
                $result = current($result);
                $this->stockStatus[$websiteId][$sku] = (int)$result->isSalable();
            }
            return $subject->validateAttribute($this->stockStatus[$websiteId][$sku]);
        }
        return $proceed($model);
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->stockStatus = [];
    }
}
