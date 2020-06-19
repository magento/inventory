<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Model\ResourceModel\Stock\Status;

use Magento\CatalogInventory\Model\ResourceModel\Stock\Status;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Model\ResourceModel\AddStatusFilterToSelect;
use Magento\InventoryCatalog\Model\ResourceModel\AddStockStatusToSelect;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\Website;

/**
 * Adapt adding stock status to select for multi stocks.
 */
class AdaptAddStockStatusToSelectPlugin
{
    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var AddStockStatusToSelect
     */
    private $addStockStatusToSelect;

    /**
     * @var AddStatusFilterToSelect
     */
    private $addStatusFilterToSelect;

    /**
     * @param StockResolverInterface $stockResolver
     * @param AddStockStatusToSelect $addStockStatusToSelect
     * @param AddStatusFilterToSelect $addStatusFilterToSelect
     */
    public function __construct(
        StockResolverInterface $stockResolver,
        AddStockStatusToSelect $addStockStatusToSelect,
        AddStatusFilterToSelect $addStatusFilterToSelect
    ) {
        $this->stockResolver = $stockResolver;
        $this->addStockStatusToSelect = $addStockStatusToSelect;
        $this->addStatusFilterToSelect = $addStatusFilterToSelect;
    }

    /**
     * Adapt adding stock status to select for multi stocks.
     *
     * @param Status $stockStatus
     * @param callable $proceed
     * @param Select $select
     * @param Website $website
     * @return Status
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Select_Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundAddStockStatusToSelect(
        Status $stockStatus,
        callable $proceed,
        Select $select,
        Website $website
    ) {
        $websiteCode = $website->getCode();
        if (null === $websiteCode) {
            throw new LocalizedException(__('Website code is empty'));
        }

        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $stockId = (int)$stock->getStockId();
        $this->addStockStatusToSelect->execute($select, $stockId);
        $this->addStatusFilterToSelect->execute($select);

        return $stockStatus;
    }
}
