<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryBundleProductIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemIds;
use Psr\Log\LoggerInterface;

/**
 * Reindex source items after bundle link has been removed plugin.
 */
class ReindexSourceItemsAfterRemoveBundleSelectionPlugin
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
     * Process source items after bundle selection has been removed.
     *
     * @param ProductLinkManagementInterface $subject
     * @param bool $result
     * @param string $sku
     * @param int $optionId
     * @param string $childSku
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRemoveChild(
        ProductLinkManagementInterface $subject,
        bool $result,
        string $sku,
        int $optionId,
        string $childSku
    ): bool {
        $skus = [$childSku];
        $children = $subject->getChildren($sku);
        foreach ($children as $child) {
            $skus[] = $child->getSku();
        }
        $skus = array_unique($skus);
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
}
