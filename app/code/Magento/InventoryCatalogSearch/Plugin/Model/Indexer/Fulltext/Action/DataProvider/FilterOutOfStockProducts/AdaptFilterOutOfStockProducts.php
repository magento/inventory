<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Plugin\Model\Indexer\Fulltext\Action\DataProvider\FilterOutOfStockProducts;

use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\DataProvider\FilterOutOfStockProducts;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventorySalesApi\Model\GetAssignedStockIdForWebsiteInterface;
use Magento\Store\Api\StoreManagementInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Filter out of stock products for index data in multi stock.
 */
class AdaptFilterOutOfStockProducts
{
    /**
     * @var GetAssignedStockIdForWebsiteInterface
     */
    private $assignedStockIdForWebsite;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var StoreManagementInterface
     */
    private $storeManager;

    /**
     * @param GetAssignedStockIdForWebsiteInterface $assignedStockIdForWebsite
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param StoreManagerInterface $storeManagement
     */
    public function __construct(
        GetAssignedStockIdForWebsiteInterface $assignedStockIdForWebsite,
        DefaultStockProviderInterface $defaultStockProvider,
        StoreManagerInterface $storeManagement
    ) {
        $this->assignedStockIdForWebsite = $assignedStockIdForWebsite;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->storeManager = $storeManagement;
    }

    /**
     * Adapt Filter out of stock products for index data in multi stock mode.
     *
     * @param FilterOutOfStockProducts $subjects
     * @param callable $proceed
     * @param array $indexData
     * @param int $storeId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        FilterOutOfStockProducts $subjects,
        callable $proceed,
        array $indexData,
        int $storeId
    ): array {
        try {
            $websiteId = (string)$this->storeManager->getStore($storeId)->getWebsiteId();
        } catch (NoSuchEntityException $e) {
            return $proceed($indexData, $storeId);
        }
        $stockId = (int)$this->assignedStockIdForWebsite->execute($websiteId);

        return $stockId === $this->defaultStockProvider->getId() ? $proceed($indexData, $storeId) : $indexData;
    }
}
