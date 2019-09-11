<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Controller\Product;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Context;
use Magento\InventoryCatalog\Model\ProductQty;
use Magento\Framework\Controller\ResultInterface;
use Magento\Store\Model\StoreManagerInterface;

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
     * @param Context $context
     * @param ResultFactory $resultPageFactory
     * @param ProductQty $productQty
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ResultFactory $resultPageFactory,
        ProductQty $productQty,
        StoreManagerInterface $storeManager
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->productQty = $productQty;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Product view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $productId = (int) $this->getRequest()->getParam('id');

        if (!$productId) {
            $resultForward = $this->resultPageFactory->create(ResultFactory::TYPE_FORWARD);
            $resultForward->forward('noroute');
            return $resultForward;
        }

        $resultJson = $this->resultPageFactory->create(ResultFactory::TYPE_JSON);
        $qty = $this->productQty->getProductQtyLeft($productId, (int)$this->storeManager->getStore()->getWebsiteId());

        if ($qty === null) {
            $resultJson->setData([]);
        } else {
            $resultJson->setData(
                [
                    'qty' => $qty
                ]
            );
        }

        return $resultJson;
    }
}
