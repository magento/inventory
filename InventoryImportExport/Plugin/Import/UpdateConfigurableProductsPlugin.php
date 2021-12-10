<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Plugin\Import;

use Magento\CatalogImportExport\Model\StockItemImporterInterface;
use Magento\ConfigurableProduct\Model\Inventory\ChangeParentStockStatus;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

class UpdateConfigurableProductsPlugin
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
     * Update configurable products stock item status based on children products stock status after import
     *
     * @param StockItemImporterInterface $subject
     * @param void $result
     * @param array $stockData
     * @return void
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
