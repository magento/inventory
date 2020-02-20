<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminGws\Plugin\InventoryApi\Api\StockRepository;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\InventoryAdminGws\Model\IsStockAllowedForCurrentUser;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;

/**
 * Restrict stock by websites for current user.
 */
class RestrictSaveStockPlugin
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
     * Verify, if stock allowed to be saved for current user.
     *
     * @param StockRepositoryInterface $subject
     * @param StockInterface $stock
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws CouldNotSaveException
     */
    public function beforeSave(
        StockRepositoryInterface $subject,
        StockInterface $stock
    ): void {
        if (!$this->isStockAllowedForCurrentUser->execute($stock)) {
            throw new CouldNotSaveException(__('Not enough permissions to operate with inventory.'));
        }
    }
}
