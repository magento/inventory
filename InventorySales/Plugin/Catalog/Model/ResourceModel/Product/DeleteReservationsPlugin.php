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
 * Remove reservations after product delete plugin.
 */
class DeleteReservationsPlugin
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
     * Asynchronously remove reservations in case product has been deleted.
     *
     * @param Product $subject
     * @param Product $result
     * @param AbstractModel $product
     * @return Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDelete(
        Product $subject,
        Product $result,
        AbstractModel $product
    ): Product {
        if ($this->config->getValue('cataloginventory/options/synchronize_with_catalog')) {
            $this->publisher->publish(
                'inventory.reservations.cleanup',
                [
                    (string)$product->getSku()
                ]
            );
        }

        return $result;
    }
}
