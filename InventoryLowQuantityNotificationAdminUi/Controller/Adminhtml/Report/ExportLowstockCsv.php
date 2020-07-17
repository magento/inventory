<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\InventoryLowQuantityNotificationAdminUi\Controller\Adminhtml\Report;

use Exception;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Reports\Controller\Adminhtml\Report\Product as ProductReportController;

/**
 * Export low stock products in CSV format
 */
class ExportLowstockCsv extends ProductReportController implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Reports::report_products';

    /**
     * Export low stock products report to CSV format
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function execute()
    {
        try {
            $this->_view->loadLayout('reports_report_product_lowstock');
            $fileName = 'products_lowstock.csv';
            $exportBlock = $this->_view->getLayout()->getChildBlock(
                'adminhtml.block.report.product.inventory.lowstock.grid',
                'grid.export'
            );
            return $this->_fileFactory->create(
                $fileName,
                $exportBlock->getCsvFile(),
                DirectoryList::VAR_DIR
            );
        } catch (Exception $e) {
            throw new LocalizedException(__('Could not export low stock report'), $e);
        }
    }
}
