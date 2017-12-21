<?php

namespace Magento\ProductAlert\Block\Adminhtml\Catalog\Product\Edit\Tab\Alerts\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Block\Context;
use Magento\InventorySales\Model\StockResolver;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\InventoryApi\Api\Data\StockInterface;

class Stock extends AbstractRenderer
{
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var StockResolver
     */
    private $stockResolver;

    /**
     * @var ManagerInterface
     */
    private $_messageManager;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param StockResolver $stockResolver
     * @param ManagerInterface $messageManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        StockResolver $stockResolver,
        ManagerInterface $messageManager,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->_messageManager = $messageManager;
    }

    /**
     * Handles Stock column
     *
     * @param DataObject $row
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(DataObject $row): string
    {
        $columnHtml = $this->getColumnHtml($row);
        if ($columnHtml) {
            return $columnHtml;
        }
        return parent::render($row);
    }

    /**
     * @param $row DataObject
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getColumnHtml(DataObject $row): string
    {
        /** @var WebsiteInterface $website */
        $website = $this->_storeManager->getWebsite($row->getWebsiteId());

        if (!$website) {
            return '';
        }
        $html = __('Website ID: %1', $website->getId()) ;
        if ($stockName = $this->getStockName($website)) {
            $html .= '<br/>';
            $html .= __('Stock: %1', $stockName);
        }
        return $html;
    }

    /**
     * @param $website WebsiteInterface
     * @return string
     */
    private function getStockName(WebsiteInterface $website): string
    {
        try {
            $stockData = $this->getStockByCode($website->getCode());
        } catch (\Exception $e) {
            $this->_messageManager->addErrorMessage($e->getMessage());
            return '';
        }
        return $stockData->getName();
    }

    /**
     * @param $websiteCode
     * @return StockInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStockByCode(string $websiteCode): StockInterface
    {
        return $this->stockResolver->get(
            SalesChannelInterface::TYPE_WEBSITE,
            $websiteCode
        );
    }

}
