<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\Type\Simple;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\Simple;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Model\GetAssignedStockIdForWebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Apply the inventory is-salable result to the according method of the product type model
 */
class IsSalablePlugin
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
     * Fetches is salable status from multi-stock
     *
     * @param Simple $subject
     * @param callable $proceed
     * @param Product $product
     * @return bool
     * @throws LocalizedException
     */
    public function aroundIsSalable(Simple $subject, callable $proceed, Product $product)
    {
        $sku = $subject->getSku($product);
        $currentWebsite = $this->storeManager->getWebsite();
        $stockId = $this->getAssignedStockIdForWebsite->execute($currentWebsite->getCode());
        $results = $this->areProductsSalable->execute([$sku], $stockId);

        foreach ($results as $result) {
            if ($result->getSku() === $sku) {
                return $result->isSalable();
            }
        }

        return $proceed();
    }
}
