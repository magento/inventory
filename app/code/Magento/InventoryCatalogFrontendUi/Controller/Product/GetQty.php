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
use Magento\Store\Model\StoreManagerInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param Context $context
     * @param ResultFactory $resultPageFactory
     * @param GetProductQtyLeft $productQty
     * @param StoreManagerInterface $storeManager
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        Context $context,
        ResultFactory $resultPageFactory,
        GetProductQtyLeft $productQty,
        StoreManagerInterface $storeManager,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        StockResolverInterface $stockResolver
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->productQty = $productQty;
        $this->storeManager = $storeManager;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->stockResolver = $stockResolver;
        parent::__construct($context);
    }

    /**
     * Get qty left for product.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $productId = (int) $this->getRequest()->getParam('id');
        $salesChannel = $this->getRequest()->getParam('channel');

        if (!$productId || !$salesChannel) {
            return $this->getResultForward();
        }

        try {
            $sku = $this->getSkusByProductIds->execute([$productId])[$productId];
        } catch (NoSuchEntityException $e) {
            return $this->getResultForward();
        }

        $websiteCode = $this->storeManager->getWebsite()->getCode();
        $stockId = $this->stockResolver->execute($salesChannel, $websiteCode)->getStockId();

        $resultJson = $this->resultPageFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData(
            [
                'qty' => $this->productQty->execute($sku, (int)$stockId)
            ]
        );

        return $resultJson;
    }

    /**
     * Get result forward.
     *
     * @return ResultInterface
     */
    private function getResultForward()
    {
        $resultForward = $this->resultPageFactory->create(ResultFactory::TYPE_FORWARD);
        $resultForward->forward('noroute');
        return $resultForward;
    }
}
