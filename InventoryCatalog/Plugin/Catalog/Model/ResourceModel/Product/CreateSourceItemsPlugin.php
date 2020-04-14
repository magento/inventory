<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\Model\AbstractModel;
use Magento\Inventory\Model\ResourceModel\SourceItem\SaveMultiple;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;

/**
 * Create source items after product save plugin.
 */
class CreateSourceItemsPlugin
{
    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var SaveMultiple
     */
    private $saveMultiple;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param SaveMultiple $saveMultiple
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        SaveMultiple $saveMultiple,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->saveMultiple = $saveMultiple;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * Create non-default source items for new sku after product sku has been changed via web-api.
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
        $sourceItems = $this->getSourceItemsBySku->execute($origSku);
        foreach ($sourceItems as $key => $sourceItem) {
            if ($sourceItem->getSourceCode() === $this->defaultSourceProvider->getCode()) {
                unset($sourceItems[$key]);
                continue;
            }
            $sourceItem->setSku($product->getSku());
        }
        if ($sourceItems) {
            $this->saveMultiple->execute($sourceItems);
        }

        return $result;
    }
}
