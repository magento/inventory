<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Framework\App\ObjectManager;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request;

/**
 * Service for get stock id for current website.
 */
class GetStockIdForCurrentWebsite
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var Request
     */
    private Request $request;

    /**
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param Request|null $request
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        Request $request = null
    ) {
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->request = $request ?: ObjectManager::getInstance()->get(Request::class);
    }

    /**
     * Determine stock id in use based on current store context
     *
     * @return int
     */
    public function execute(): int
    {
        $storeId = $this->request->getParam('store');
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();

        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $stockId = (int)$stock->getStockId();

        return $stockId;
    }
}
