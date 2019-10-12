<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesAdminUi\ViewModel\CreateOrder;

use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupSalesAdminUi\Model\GetPickupSources;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * ViewModel for Magento_InventoryInStorePickupSalesAdminUi::order/create/shipping/method/sources_form.phtml
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class SourcesForm implements ArgumentInterface
{
    /**
     * @var Quote
     */
    private $backendQuote;

    /**
     * @var GetPickupSources
     */
    private $getPickupSources;

    /**
     * @var array
     */
    private $pickupSources = [];

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param Quote $backendQuote
     * @param GetPickupSources $getPickupSources
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        Quote $backendQuote,
        GetPickupSources $getPickupSources,
        StockResolverInterface $stockResolver
    ) {
        $this->backendQuote = $backendQuote;
        $this->getPickupSources = $getPickupSources;
        $this->stockResolver = $stockResolver;
    }

    /**
     * Get list of inventory sources assigned as pickup locations.
     *
     * @return array [sourceCode => sourceName]
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getPickupSourcesOptionsList(): array
    {
        if (!empty($this->pickupSources)) {
            return $this->pickupSources;
        }

        $pickupSources = $this->getPickupSources->execute($this->getStockId());
        $this->pickupSources = [];
        /** @var SourceInterface $source */
        foreach ($pickupSources as $source) {
            $this->pickupSources[$source->getSourceCode()] = $source->getName();
        }

        return $this->pickupSources;
    }

    /**
     * Get stock id assigned to quote.
     *
     * @return int|null
     * @throws NoSuchEntityException
     */
    private function getStockId()
    {
        return $this->stockResolver->execute(
            SalesChannelInterface::TYPE_WEBSITE,
            $this->backendQuote->getStore()->getWebsite()->getCode()
        )->getStockId();
    }
}
