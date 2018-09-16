<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\InventoryApi\Model\IsProductAssignedToStockInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;

class IsRequestedSkuAssignedToStock
{
    /**
     * @var IsProductAssignedToStockInterface
     */
    private $isProductAssignedToStock;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var IsSourceItemManagementAllowedForSku
     */
    private $isSourceItemManagementAllowedForSku;

    /**
     * @param IsProductAssignedToStockInterface $isProductAssignedToStock
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param IsSourceItemManagementAllowedForSku $isSourceItemManagementAllowedForSku
     */
    public function __construct(
        IsProductAssignedToStockInterface $isProductAssignedToStock,
        DefaultStockProviderInterface $defaultStockProvider,
        IsSourceItemManagementAllowedForSku $isSourceItemManagementAllowedForSku
    ) {
        $this->isProductAssignedToStock = $isProductAssignedToStock;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->isSourceItemManagementAllowedForSku = $isSourceItemManagementAllowedForSku;
    }

    /**
     * @param string $sku
     * @param int $stockId
     * @throws SkuIsNotAssignedToStockException
     */
    public function execute(string $sku, int $stockId): void
    {
        if ($this->defaultStockProvider->getId() !== $stockId
            && true === $this->isSourceItemManagementAllowedForSku->execute($sku)
            && false === $this->isProductAssignedToStock->execute($sku, $stockId)) {
            throw new SkuIsNotAssignedToStockException(
                __('The requested sku is not assigned to given stock.')
            );
        }
    }
}
