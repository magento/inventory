<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\ViewModel\CreateOrder;

use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupAdminUi\Model\GetPickupSources;

/**
 * ViewModel for Magento_InventoryInStorePickupAdminUi::order/create/shipping/method/sources_form.phtml
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
     * @var array|null
     */
    private $pickupLocationsArray;

    /**
     * @param Quote $backendQuote
     * @param GetPickupSources $getPickupSources
     */
    public function __construct(
        Quote $backendQuote,
        GetPickupSources $getPickupSources
    ) {
        $this->backendQuote = $backendQuote;
        $this->getPickupSources = $getPickupSources;
    }

    /**
     * @return string|null
     */
    public function getSelectedPickupLocationCode(): ?string
    {
        //TODO
        return 'zzz';
    }

    /**
     * @return array
     *  [sourceCode => SourceName]
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getPickupLocationsArray(): array
    {
        if ($this->pickupLocationsArray === null) {
            $this->initializePickupLocationsArray();
        }

        return $this->pickupLocationsArray;
    }

    /**
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function initializePickupLocationsArray(): void
    {
        $pickupSources = $this->getPickupSources->execute($this->backendQuote->getStore()->getWebsite()->getCode());

        $this->pickupLocationsArray = [];
        /** @var SourceInterface $source */
        foreach ($pickupSources as $source) {
            $this->pickupLocationsArray[$source->getSourceCode()] = $source->getName();
        }
    }
}
