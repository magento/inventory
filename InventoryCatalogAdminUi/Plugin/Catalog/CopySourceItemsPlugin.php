<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Plugin\Catalog;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Copier;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\InputException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryCatalogApi\Model\SourceItemsProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * Copies source items from the original product to the duplicate
 */
class CopySourceItemsPlugin
{
    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var SourceItemsProcessorInterface
     */
    private $sourceItemsProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param SourceItemsProcessorInterface $sourceItemsProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        SourceItemRepositoryInterface $sourceItemRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        SourceItemsProcessorInterface $sourceItemsProcessor,
        LoggerInterface $logger
    ) {
        $this->sourceItemRepository = $sourceItemRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->sourceItemsProcessor = $sourceItemsProcessor;
        $this->logger = $logger;
    }

    /**
     * Copy source items after product has been copied.
     *
     * @param Copier $subject
     * @param Product $result
     * @param Product $product
     * @return Product $result
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCopy(
        Copier $subject,
        Product $result,
        Product $product
    ) {
        $this->copySourceItems($product->getSku(), $result->getSku());
        $result->setStatus(Status::STATUS_DISABLED);

        return $result;
    }

    /**
     * Retrieve source items for given product sku.
     *
     * @param string $sku
     * @return array
     */
    private function getSourceItems(string $sku): array
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->create();
        return $this->sourceItemRepository->getList($searchCriteria)->getItems();
    }

    /**
     * Copy source items from original product to copy.
     *
     * @param string $originalSku
     * @param string $duplicateSku
     * @return void
     */
    private function copySourceItems(string $originalSku, string $duplicateSku): void
    {
        $sourceItems = $this->getSourceItems($originalSku);

        $duplicateItemData = [];
        if ($sourceItems) {
            foreach ($sourceItems as $sourceItem) {
                $duplicateItemData[] = [
                    SourceItemInterface::SKU => $duplicateSku,
                    SourceItemInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                    SourceItemInterface::QUANTITY => $sourceItem->getQuantity(),
                    SourceItemInterface::STATUS => $sourceItem->getStatus(),
                ];
            }
        }

        try {
            $this->sourceItemsProcessor->execute(
                (string)$duplicateSku,
                $duplicateItemData
            );
        } catch (InputException $e) {
            $this->logger->error($e->getLogMessage());
        }
    }
}
