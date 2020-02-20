<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminGws\Plugin\CatalogInventory\Api\StockItemRepository;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\InventoryAdminGws\Model\IsSourceAllowedForCurrentUser;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;

/**
 * Restrict source items by websites for current user.
 */
class RestrictSaveSourceItemPlugin
{
    /**
     * @var IsSourceAllowedForCurrentUser
     */
    private $isSourceAllowedForCurrentUser;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @param IsSourceAllowedForCurrentUser $isSourceAllowedForCurrentUser
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        IsSourceAllowedForCurrentUser $isSourceAllowedForCurrentUser,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->isSourceAllowedForCurrentUser = $isSourceAllowedForCurrentUser;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * Verify, if legacy source item could be saved.
     *
     * @param StockItemRepositoryInterface $subject
     * @param \Closure $proceed
     * @param StockItemInterface $stockItem
     * @return StockItemInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSave(
        StockItemRepositoryInterface $subject,
        \Closure $proceed,
        StockItemInterface $stockItem
    ): StockItemInterface {
        if ($this->isSourceAllowedForCurrentUser->execute($this->defaultSourceProvider->getCode())) {
            $proceed($stockItem);
        }

        return $stockItem;
    }
}
