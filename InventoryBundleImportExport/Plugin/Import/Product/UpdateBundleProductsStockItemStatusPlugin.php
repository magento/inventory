<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleImportExport\Plugin\Import\Product;

use Magento\Bundle\Model\Inventory\ChangeParentStockStatus;
use Magento\CatalogImportExport\Model\StockItemImporterInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * Allow automatic parent stock update in single source mode only
 */
class UpdateBundleProductsStockItemStatusPlugin
{
    /**
     * @var ChangeParentStockStatus
     */
    private $changeParentStockStatus;

    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @param ChangeParentStockStatus $changeParentStockStatus
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     */
    public function __construct(
        ChangeParentStockStatus $changeParentStockStatus,
        IsSingleSourceModeInterface $isSingleSourceMode
    ) {
        $this->changeParentStockStatus = $changeParentStockStatus;
        $this->isSingleSourceMode = $isSingleSourceMode;
    }

    /**
     * Allow automatic parent stock update in single source mode only
     *
     * @param StockItemImporterInterface $subject
     * @param mixed $result
     * @param array $stockData
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterImport(
        StockItemImporterInterface $subject,
        $result,
        array $stockData
    ): void {
        if ($stockData && $this->isSingleSourceMode->execute()) {
            $this->changeParentStockStatus->execute(array_column($stockData, 'product_id'));
        }
    }
}
