<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryProductAlert\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\InventorySales\Model\SalesChannelByWebsiteCodeProvider;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\ProductAlert\Model\ProductSalability;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Adapt product salability for multi source.
 */
class AdaptProductSalabilityPlugin
{
    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var SalesChannelByWebsiteCodeProvider
     */
    private $salesChannelByWebsiteCodeProvider;

    /**
     * @param IsProductSalableInterface $isProductSalable
     * @param SalesChannelByWebsiteCodeProvider $salesChannelByWebsiteCodeProvider
     */
    public function __construct(
        IsProductSalableInterface $isProductSalable,
        SalesChannelByWebsiteCodeProvider $salesChannelByWebsiteCodeProvider
    ) {
        $this->isProductSalable = $isProductSalable;
        $this->salesChannelByWebsiteCodeProvider = $salesChannelByWebsiteCodeProvider;
    }

    /**
     * @param  ProductSalability $productSalability
     * @param callable $proceed
     * @param ProductInterface $product
     * @param WebsiteInterface $website
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsSalable(
        ProductSalability $productSalability,
        callable $proceed,
        ProductInterface $product,
        WebsiteInterface $website
    ): bool {
        $salesChannel = $this->salesChannelByWebsiteCodeProvider->execute($website->getCode());
        return $this->isProductSalable->execute($product->getSku(), $salesChannel);
    }
}
