<?php

namespace Magento\ProductAlert\Block\Adminhtml\Catalog\Product\Edit\Tab\Alerts;

use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\ProductAlert\Block\Adminhtml\Catalog\Product\Edit\Tab\Alerts\Renderer\Stock as StockRenderer;

class Stock extends \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Alerts\Stock
{
    /**
     * @return $this
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn('firstname', ['header' => __('First Name'), 'index' => 'firstname']);

        $this->addColumn('lastname', ['header' => __('Last Name'), 'index' => 'lastname']);

        $this->addColumn('email', ['header' => __('Email'), 'index' => 'email']);

        $this->addColumn('add_date', ['header' => __('Subscribe Date'), 'index' => 'add_date', 'type' => 'date']);

        $this->addColumn(
            'send_date',
            ['header' => __('Last Notified'), 'index' => 'send_date', 'type' => 'date']
        );

        $this->addColumn('send_count', ['header' => __('Send Count'), 'index' => 'send_count']);

        $this->addColumn(
            'stock',
            [
                'header' => __('Stock'),
                'index' => 'stock_data',
                'renderer' => StockRenderer::class
            ]
        );

        return Extended::_prepareColumns();
    }
}
