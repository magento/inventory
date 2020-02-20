<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminGws\Plugin\InventoryApi\Api\StockRepository;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryAdminGws\Model\IsStockAllowedForCurrentUser;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;

/**
 * Restrict stock by websites for current user.
 */
class RestrictGetStockPlugin
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
     * Filter restricted stock for current user.
     *
     * @param StockRepositoryInterface $subject
     * @param StockInterface $stock
     * @return StockInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws NoSuchEntityException
     */
    public function afterGet(
        StockRepositoryInterface $subject,
        StockInterface $stock
    ): StockInterface {
        if (!$this->isStockAllowedForCurrentUser->execute($stock)) {
            throw new NoSuchEntityException();
        }

        return $stock;
    }
}
