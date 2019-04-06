<?php
/**
 * MageSpecialist
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magespecialist.it so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2019 Skeeller srl (http://www.magespecialist.it)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\InventoryBundleProduct\Plugin\Model\ResourceModel\Selection;

use Magento\Bundle\Model\ResourceModel\Selection\Collection;
use Magento\Catalog\Model\ResourceModel\Product\BaseSelectProcessorInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\DB\Select;
use Magento\InventoryIndexer\Indexer\IndexStructure;

class CollectionPlugin
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var StockResolverInterface
     */
    private $stockResolver;
    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;
    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * CollectionPlugin constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        DefaultStockProviderInterface $defaultStockProvider,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver
    )
    {
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
    }

    public function aroundAddQuantityFilter(Collection $subject, callable $proceed)
    {
        $websiteCode = $this->storeManager->getWebsite()->getCode();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $stockId = (int)$stock->getStockId();
        if ($stockId === $this->defaultStockProvider->getId()) {
            return $proceed();
        }

        $stockTable = $this->stockIndexTableNameResolver->execute($stockId);
        $isSalableColumnName = IndexStructure::IS_SALABLE;

        /** @var Select $select */
        $select = $subject->getSelect();
        $select->join(
            ['stock' => $stockTable],
            'stock.sku = e.sku',
            []
        );

        $select->where(sprintf('stock.%1s = ?', $isSalableColumnName), 1);

        return $select;
    }
}