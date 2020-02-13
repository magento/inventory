<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Process reservations after product save plugin.
 */
class UpdateReservationsPlugin
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
     * Asynchronously update reservations in case product sku has been changed.
     *
     * @param Product $subject
     * @param Product $result
     * @param AbstractModel $product
     * @return Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        Product $subject,
        Product $result,
        AbstractModel $product
    ): Product {
        if ($this->isUpdateNeeded($product)) {
            $this->publisher->publish(
                'inventory.reservations.update',
                [
                    (string)$product->getOrigData('sku'),
                    (string)$product->getSku(),
                ]
            );
        }

        return $result;
    }

    /**
     * Check if reservations should be updated.
     *
     * @param AbstractModel $product
     * @return bool
     */
    private function isUpdateNeeded(AbstractModel $product)
    {
        $origSku = $product->getOrigData('sku');
        return $this->config->getValue('cataloginventory/options/synchronize_with_catalog')
            && $origSku !== null && $origSku !== $product->getSku();
    }
}
