<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\Model\AbstractModel;
use Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration\GetBySku;
use Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration\SaveMultiple;
use Psr\Log\LoggerInterface;

/**
 * Create source items configuration plugin.
 */
class CreateSourceItemConfigurationPlugin
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GetBySku
     */
    private $getBySku;

    /**
     * @var SaveMultiple
     */
    private $saveMultiple;

    /**
     * @param GetBySku $getBySku
     * @param SaveMultiple $saveMultiple
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetBySku $getBySku,
        SaveMultiple $saveMultiple,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->getBySku = $getBySku;
        $this->saveMultiple = $saveMultiple;
    }

    /**
     * Create non-default source item configuration for new sku after product sku has been changed via web-api.
     *
     * @param Product $subject
     * @param Product $result
     * @param AbstractModel $product
     * @return Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(Product $subject, Product $result, AbstractModel $product): Product
    {
        $origSku = (string)$product->getOrigData('sku');
        if (!$origSku || $origSku === $product->getSku()) {
            return $result;
        }
        $sourceItemsConfigurations = $this->getBySku->execute($origSku);
        foreach ($sourceItemsConfigurations as $sourceItemConfiguration) {
            $sourceItemConfiguration->setSku($product->getSku());
        }
        if ($sourceItemsConfigurations) {
            $this->saveMultiple->execute($sourceItemsConfigurations);
        }

        return $result;
    }
}
