<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Remove source items after given product has been deleted plugin.
 */
class DeleteSourceItemsPlugin
{
    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @param PublisherInterface $publisher
     * @param ScopeConfigInterface $config
     */
    public function __construct(PublisherInterface $publisher, ScopeConfigInterface $config)
    {
        $this->publisher = $publisher;
        $this->config = $config;
    }

    /**
     * Asynchronously cleanup source items in case product has been deleted.
     *
     * @param Product $subject
     * @param Product $result
     * @param ProductInterface $product
     * @return Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDelete(Product $subject, $result, $product): Product
    {
        if ($this->config->getValue('cataloginventory/options/synchronize_with_catalog')) {
            $this->publisher->publish(
                'inventory.source.items.cleanup',
                [
                    (string)$product->getSku(),
                ]
            );
        }

        return $result;
    }
}
