<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\InventoryApi\StockRepository\PreventDeleting;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;

/**
 * Prevent deleting of Default Stock
 */
class DefaultStockPlugin
{
    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * Prevent deleting of Default Stock
     *
     * @param StockRepositoryInterface $subject
     * @param int $stockId
     * @return void
     * @throws CouldNotDeleteException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeDeleteById(StockRepositoryInterface $subject, int $stockId)
    {
        if ($stockId === $this->defaultStockProvider->getId()) {
            throw new CouldNotDeleteException(__('Default Stock could not be deleted.'));
        }
    }
}
