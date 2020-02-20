<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminGws\Plugin\InventoryApi\Api\StockRepository;

use Magento\InventoryAdminGws\Model\IsStockAllowedForCurrentUser;
use Magento\InventoryApi\Api\Data\StockSearchResultsInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;

/**
 * Restrict stocks by websites for current user.
 */
class RestrictGetStockListPlugin
{
    /**
     * @var IsStockAllowedForCurrentUser
     */
    private $isStockAllowedForCurrentUser;

    /**
     * @param IsStockAllowedForCurrentUser $isStockAllowedForCurrentUser
     */
    public function __construct(IsStockAllowedForCurrentUser $isStockAllowedForCurrentUser)
    {
        $this->isStockAllowedForCurrentUser = $isStockAllowedForCurrentUser;
    }

    /**
     * Filter restricted stocks for current user.
     *
     * @param StockRepositoryInterface $subject
     * @param StockSearchResultsInterface $result
     * @return StockSearchResultsInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        StockRepositoryInterface $subject,
        StockSearchResultsInterface $result
    ): StockSearchResultsInterface {
        $allowedStocks = [];
        foreach ($result->getItems() as $stock) {
            if ($allowed = $this->isStockAllowedForCurrentUser->execute($stock)) {
                $allowedStocks[] = $stock;
            }
        }
        $result->setItems($allowedStocks);

        return $result;
    }
}
