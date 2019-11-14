<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Plugin\InventoryAdminUi\DataProvider;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\InventoryAdminUi\Ui\DataProvider\SourceDataProvider;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;

/**
 * Disable selection of Default Source as Pickup Location.
 */
class PreventUsingDefaultSourceAsPickupLocationPlugin
{
    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param RequestInterface $request
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        DefaultSourceProviderInterface $defaultSourceProvider,
        RequestInterface $request,
        ArrayManager $arrayManager
    ) {
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->request = $request;
        $this->arrayManager = $arrayManager;
    }

    /**
     * Populate meta in case to disable possibility to mark Default Source as Pickup Location.
     *
     * @param SourceDataProvider $subject
     * @param array $meta
     * @return array
     */
    public function afterGetMeta(
        SourceDataProvider $subject,
        $meta
    ): array {
        $isFormComponent = SourceDataProvider::SOURCE_FORM_NAME === $subject->getName();
        if (!$isFormComponent || !$this->isDefaultSource()) {
            return $meta;
        }

        $path = 'general/children/is_pickup_location_active/arguments/data/config/disabled';

        $meta = $this->arrayManager->set($path, $meta, true);

        return $meta;
    }

    /**
     * Check if Source from Request is Default Source.
     *
     * @return bool
     */
    private function isDefaultSource(): bool
    {
        $defaultSourceCode = $this->defaultSourceProvider->getCode();
        $currentSourceCode = $this->request->getParam(SourceItemInterface::SOURCE_CODE);
        return $defaultSourceCode === $currentSourceCode;
    }
}
