<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Model\GetAssignedStockIdForWebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Apply the inventory is-salable result to the according method of the product model
 */
class IsProductSalablePlugin
{
    /**
     * @var GetAssignedStockIdForWebsiteInterface
     */
    private $getAssignedStockIdForWebsite;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @param GetAssignedStockIdForWebsiteInterface $getAssignedStockIdForWebsite
     * @param StoreManagerInterface $storeManager
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        GetAssignedStockIdForWebsiteInterface $getAssignedStockIdForWebsite,
        StoreManagerInterface $storeManager,
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->getAssignedStockIdForWebsite = $getAssignedStockIdForWebsite;
        $this->storeManager = $storeManager;
        $this->areProductsSalable = $areProductsSalable;
    }

    /**
     * @param Product $subject
     * @param callable $proceed
     * @return bool
     * @throws LocalizedException
     */
    public function aroundIsSalable(Product $subject, callable $proceed)
    {
        $sku = $subject->getSku();
        $currentWebsite = $this->storeManager->getWebsite();
        $results = $this->areProductsSalable->execute(
            [$sku],
            $this->getAssignedStockIdForWebsite->execute($currentWebsite->getCode())
        );

        foreach ($results as $result) {
            if ($result->getSku() === $sku) {
                return $result->isSalable();
            }
        }

        return $proceed();
    }
}
