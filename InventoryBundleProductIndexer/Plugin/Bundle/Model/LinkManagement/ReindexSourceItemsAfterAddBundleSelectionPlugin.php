<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryBundleProductIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemIds;
use Psr\Log\LoggerInterface;

/**
 * Reindex source items after bundle link has been added plugin.
 */
class ReindexSourceItemsAfterAddBundleSelectionPlugin
{
    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var SourceItemIndexer
     */
    private $sourceItemIndexer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GetSourceItemIds
     */
    private $getSourceItemIds;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param SourceItemIndexer $sourceItemIndexer
     * @param GetSourceItemIds $getSourceItemIds
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        SourceItemIndexer $sourceItemIndexer,
        GetSourceItemIds $getSourceItemIds,
        LoggerInterface $logger
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->sourceItemIndexer = $sourceItemIndexer;
        $this->logger = $logger;
        $this->getSourceItemIds = $getSourceItemIds;
    }

    /**
     * Reindex source items after selection has been added to bundle product.
     *
     * @param ProductLinkManagementInterface $subject
     * @param int $result
     * @param ProductInterface $product
     * @param int $optionId
     * @param LinkInterface $linkedProduct
     * @return int
     * @throws InputException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAddChild(
        ProductLinkManagementInterface $subject,
        int $result,
        ProductInterface $product,
        int $optionId,
        LinkInterface $linkedProduct
    ): int {
        $skus = $this->getBundleSelectionsSkus($subject, $product, $linkedProduct);
        $sourceItems = [];
        foreach ($skus as $sku) {
            $sourceItems[] = $this->getSourceItemsBySku->execute($sku);
        }
        $sourceItems = array_merge(...$sourceItems);
        $sourceItemIds = $this->getSourceItemIds->execute($sourceItems);
        try {
            $this->sourceItemIndexer->executeList($sourceItemIds);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }

    /**
     * Retrieve bundle selections skus.
     *
     * @param ProductLinkManagementInterface $productLinkManagement
     * @param ProductInterface $product
     * @param LinkInterface $link
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function getBundleSelectionsSkus(
        ProductLinkManagementInterface $productLinkManagement,
        ProductInterface $product,
        LinkInterface $link
    ): array {
        $skus = [];
        $bundleSelectionsData = $product->getBundleSelectionsData() ?: [];
        foreach ($bundleSelectionsData as $option) {
            $skus[] = array_column($option, 'sku');
        }
        $skus = $skus ? array_merge(...$skus) : $skus;
        if (!$skus) {
            $skus = [$link->getSku()];
            $children = $productLinkManagement->getChildren($product->getSku());
            foreach ($children as $child) {
                $skus[] = $child->getSku();
            }
        }

        return $skus;
    }
}
