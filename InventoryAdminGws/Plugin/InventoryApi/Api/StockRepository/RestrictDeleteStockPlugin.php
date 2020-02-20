<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminGws\Plugin\InventoryApi\Api\StockRepository;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryAdminGws\Model\IsStockAllowedForCurrentUser;
use Magento\InventoryApi\Api\StockRepositoryInterface;

/**
 * Restrict stock by websites for current user.
 */
class RestrictDeleteStockPlugin
{
    /**
     * @var IsStockAllowedForCurrentUser
     */
    private $isStockAllowedForCurrentUser;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @param IsStockAllowedForCurrentUser $isStockAllowedForCurrentUser
     * @param StockRepositoryInterface $stockRepository
     */
    public function __construct(
        IsStockAllowedForCurrentUser $isStockAllowedForCurrentUser,
        StockRepositoryInterface $stockRepository
    ) {
        $this->isStockAllowedForCurrentUser = $isStockAllowedForCurrentUser;
        $this->stockRepository = $stockRepository;
    }

    /**
     * Verify, if stock allowed to be deleted for current user.
     *
     * @param StockRepositoryInterface $subject
     * @param int $stockId
     * @return void
     * @throws CouldNotDeleteException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeDeleteById(
        StockRepositoryInterface $subject,
        int $stockId
    ): void {
        try {
            $this->stockRepository->get($stockId);
        } catch (NoSuchEntityException $e) {
            throw new CouldNotDeleteException(__('Not enough permissions to operate with inventory.'));
        }
    }
}
