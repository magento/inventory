<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Model;

/**
 * Mass assign session storage
 * @see \Magento\InventoryCatalogAdminUi\Controller\Adminhtml\Source\BulkAssign
 * @see \Magento\InventoryCatalogAdminUi\Controller\Adminhtml\Source\BulkUnassign
 * @see \Magento\InventoryCatalogAdminUi\Controller\Adminhtml\Inventory\BulkTransfer
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * phpcs:disable Magento2.Classes.DiscouragedDependencies
 */
class BulkSessionProductsStorage
{
    /**
     * @var \Magento\Backend\Model\Session\Proxy
     */
    private $session;

    /**
     * @param \Magento\Backend\Model\Session\Proxy $session
     */
    public function __construct(
        // @phpstan-ignore-next-line
        \Magento\Backend\Model\Session\Proxy $session
    ) {
        $this->session = $session;
    }

    /**
     * Set product SKUs
     *
     * @param array $productIds
     */
    public function setProductsSkus(array $productIds): void
    {
        $this->session->setProductSkus($productIds);
    }

    /**
     * Get selected product SKUs
     *
     * @return array
     */
    public function getProductsSkus(): array
    {
        return $this->session->getProductSkus();
    }
}
