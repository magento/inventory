<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Api\ProductLinkManagementAddChildrenInterface;
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
class ReindexSourceItemsAfterBulkAddBundleSelectionPlugin
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
     * @var ProductLinkManagementInterface
     */
    private $linkManagement;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param SourceItemIndexer $sourceItemIndexer
     * @param GetSourceItemIds $getSourceItemIds
     * @param LoggerInterface $logger
     * @param ProductLinkManagementInterface $linkManagement
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        SourceItemIndexer $sourceItemIndexer,
        GetSourceItemIds $getSourceItemIds,
        LoggerInterface $logger,
        ProductLinkManagementInterface $linkManagement
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->sourceItemIndexer = $sourceItemIndexer;
        $this->logger = $logger;
        $this->getSourceItemIds = $getSourceItemIds;
        $this->linkManagement = $linkManagement;
    }

    /**
     * Reindex source items after selection has been added to bundle product.
     *
     * @param ProductLinkManagementAddChildrenInterface $subject
     * @param void $result
     * @param ProductInterface $product
     * @param int $optionId
     * @param LinkInterface[] $linkedProducts
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAddChildren(
        ProductLinkManagementAddChildrenInterface $subject,
                                                  $result,
        ProductInterface                          $product,
        int                                       $optionId,
        array                                     $linkedProducts
    ): void {
        $skus = $this->getBundleSelectionsSkus($product, $linkedProducts);
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
    }

    /**
     * Retrieve bundle selections skus.
     *
     * @param ProductInterface $product
     * @param LinkInterface[] $links
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function getBundleSelectionsSkus(
        ProductInterface $product,
        array $links
    ): array {
        $skus = [];
        $bundleSelectionsData = $product->getBundleSelectionsData() ?: [];
        foreach ($bundleSelectionsData as $option) {
            $skus[] = array_column($option, 'sku');
        }
        $skus = $skus ? array_merge(...$skus) : $skus;
        if (!$skus) {
            foreach ($links as $link) {
                $skus[] = $link->getSku();
            }
            $children = $this->linkManagement->getChildren($product->getSku());
            foreach ($children as $child) {
                $skus[] = $child->getSku();
            }
        }

        return $skus;
    }
}
