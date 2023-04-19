<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\AbstractResource;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurableProductIndexer\Indexer\SourceItem\SourceItemIndexer;

class APISourceItemIndexerPlugin
{
    /**
     * @var SourceItemIndexer
     */
    private SourceItemIndexer $configurableProductsSourceItemIndexer;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private GetSourceItemsBySkuInterface $getSourceItemsBySku;

    /**
     * @var DefaultSourceProviderInterface
     */
    private DefaultSourceProviderInterface $defaultSourceProvider;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private GetSkusByProductIdsInterface $skuProvider;

    /**
     * @param SourceItemIndexer $configurableProductsSourceItemIndexer
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param GetSkusByProductIdsInterface $getSkusByProductIdsInterface
     */
    public function __construct(
        SourceItemIndexer              $configurableProductsSourceItemIndexer,
        GetSourceItemsBySkuInterface   $getSourceItemsBySku,
        DefaultSourceProviderInterface $defaultSourceProvider,
        GetSkusByProductIdsInterface   $getSkusByProductIdsInterface
    ) {
        $this->configurableProductsSourceItemIndexer = $configurableProductsSourceItemIndexer;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->skuProvider = $getSkusByProductIdsInterface;
    }

    /**
     * Once the product has been saved, perform stock reindex
     *
     * @param ProductResource $subject
     * @param AbstractResource $result
     * @param AbstractModel $object
     * @return AbstractResource
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        ProductResource  $subject,
        AbstractResource $result,
        AbstractModel    $object
    ): AbstractResource {
        if ($object instanceof Product && $object->getTypeId() == Configurable::TYPE_CODE) {
            $childProductIds = $object->getTypeInstance()->getChildrenIds($object->getId());
            $sourceItemIds = [];
            foreach ($childProductIds as $productIds) {
                if (empty($productIds)) {
                    continue;
                }
                foreach ($productIds as $productId) {
                    $childProductSku = $this->skuProvider->execute([$productId])[$productId];
                    $sourceItems = $this->getSourceItemsBySku->execute($childProductSku);
                    foreach ($sourceItems as $key => $sourceItem) {
                        if ($sourceItem->getSourceCode() === $this->defaultSourceProvider->getCode()) {
                            unset($sourceItems[$key]);
                            continue;
                        }
                        $sourceItem->setSku($object->getSku());
                        $sourceItemIds[] = $sourceItem->getId();
                    }
                }
            }
            if ($sourceItemIds) {
                $this->configurableProductsSourceItemIndexer->executeList($sourceItemIds);
            }
        }

        return $result;
    }
}
