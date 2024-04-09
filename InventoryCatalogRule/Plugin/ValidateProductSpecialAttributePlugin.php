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

namespace Magento\InventoryCatalogRule\Plugin;

use Magento\Framework\Model\AbstractModel;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Rule\Model\Condition\Product\AbstractProduct;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Plugin to customize specific condition for product attributes
 */
class ValidateProductSpecialAttributePlugin
{
    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param AreProductsSalableInterface $areProductsSalable
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        AreProductsSalableInterface $areProductsSalable,
        WebsiteRepositoryInterface $websiteRepository,
        StockResolverInterface $stockResolver,
    ) {
        $this->areProductsSalable = $areProductsSalable;
        $this->websiteRepository = $websiteRepository;
        $this->stockResolver = $stockResolver;
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
        if ('quantity_and_stock_status' === $subject->getAttribute()) {
            $website = $this->websiteRepository->getById((int)$model->getStore()->getWebsiteId());
            $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
            $result = $this->areProductsSalable->execute([$model->getSku()], $stock->getStockId());
            $result = current($result);
            return $subject->validateAttribute((int)$result->isSalable());
        }
        return $proceed($model);
    }
}
