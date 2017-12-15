<?php

namespace Magento\ProductAlert\Block\Adminhtml\Catalog\Product\Edit\Tab\Alerts\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Block\Context;
use Magento\InventorySales\Model\StockResolver;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

class Stock extends AbstractRenderer
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var StockResolver
     */
    protected $_stockResolver;

    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * Stock constructor.
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
        $this->_stockResolver = $stockResolver;
        $this->_messageManager = $messageManager;
    }

    /**
     * Handles Stock column
     *
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        try {
            $value = $this->getColumnData($row);
            return $value;
        } catch (\Exception $exception) {
            $this->_messageManager->addErrorMessage($exception);
        }
        return parent::render($row);
    }

    /**
     * @param $row
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getColumnData($row)
    {
        /** @var \Magento\Store\Api\Data\WebsiteInterface $website */
        $website = $this->_storeManager->getWebsite($row->getWebsiteId());

        if (!$website) {
            return '';
        }
        $websiteStr = $this->getWebsiteStr($website);
        $stockName = $this->getStockStr($website);
        $resultStr = $websiteStr . '<br/>' . $stockName;
        return $resultStr;
    }

    /**
     * @param $website \Magento\Store\Api\Data\WebsiteInterface
     * @return string
     */
    public function getWebsiteStr($website)
    {
        if (!$website) {
            return '';
        }
        return __('Website ID: %1', $website->getId()) . '<br/>';
    }

    /**
     * @param $website \Magento\Store\Api\Data\WebsiteInterface
     * @return string
     */
    public function getStockStr($website)
    {
        if (!$website) {
            return '';
        }
        try {
            $salesChannel = $this->getSalesChannelByCode($website->getCode());
        } catch (\Exception $e) {
            $this->_messageManager->addErrorMessage($e->getMessage());
            return '';
        }
        return __('Stock: %1', $salesChannel->getName());
    }

    /**
     * @param $websiteCode
     * @return \Magento\InventoryApi\Api\Data\StockInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getSalesChannelByCode($websiteCode)
    {
        $stockResolver = $this->_stockResolver;
        return $stockResolver->get(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
    }

}