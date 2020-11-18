<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogFrontendUi\Controller\Product;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Context;
use Magento\InventoryCatalogFrontendUi\Model\GetProductQtyLeft;
use Magento\Framework\Controller\ResultInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Get product qty left.
 */
class GetQty extends Action implements HttpGetActionInterface
{
    /**
     * @var ResultFactory
     */
    private $resultPageFactory;

    /**
     * @var ProductQty
     */
    private $productQty;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param Context $context
     * @param ResultFactory $resultPageFactory
     * @param GetProductQtyLeft $productQty
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        Context $context,
        ResultFactory $resultPageFactory,
        GetProductQtyLeft $productQty,
        StockResolverInterface $stockResolver
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->productQty = $productQty;
        $this->stockResolver = $stockResolver;
    }

    /**
     * Get qty left for product.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $sku = $this->getRequest()->getParam('sku');
        $salesChannel = $this->getRequest()->getParam('channel');
        $salesChannelCode = $this->getRequest()->getParam('salesChannelCode');
        $resultJson = $this->resultPageFactory->create(ResultFactory::TYPE_JSON);

        if (!$sku || $salesChannel==null || $salesChannelCode==null) {
            $resultJson->setData(
                [
                    'qty' => null
                ]
            );
        } else {
            try {
                $stockId = $this->stockResolver->execute($salesChannel, $salesChannelCode)->getStockId();
                $qty = $this->productQty->execute($sku, (int)$stockId);
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $qty = null;
            }

            $resultJson->setData(
                [
                    'qty' => $qty
                ]
            );
        }

        return $resultJson;
    }
}
