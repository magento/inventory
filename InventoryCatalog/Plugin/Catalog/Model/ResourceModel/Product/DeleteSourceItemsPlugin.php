<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\Amqp\Config;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
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
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @param PublisherInterface $publisher
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(PublisherInterface $publisher, DeploymentConfig $deploymentConfig)
    {
        $this->publisher = $publisher;
        $this->deploymentConfig = $deploymentConfig;
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
        try {
            $configData = $this->deploymentConfig->getConfigData(Config::QUEUE_CONFIG) ?: [];
        } catch (FileSystemException|RuntimeException $e) {
            $configData = [];
        }
        $topic = isset($configData[Config::AMQP_CONFIG][Config::HOST])
            ? 'async.inventory.source.items.cleanup'
            : 'async.inventory.source.items.cleanup.db';
        $this->publisher->publish($topic, [[(string)$product->getSku()]]);

        return $result;
    }
}
