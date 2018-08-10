<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShippingAdminUi\Model;

use Magento\InventoryShippingAdminUi\Model\SourceSelectionResultAdapterFactory;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface;
use Magento\InventorySourceSelectionApi\Api\SourceSelectionServiceInterface;

class SourceSelectionResultAdapterFromRequestItemsFactory
{
    /**
     * @var GetDefaultSourceSelectionAlgorithmCodeInterface
     */
    private $getDefaultSourceSelectionAlgorithmCode;

    /**
     * @var SourceSelectionServiceInterface
     */
    private $sourceSelectionService;

    /**
     * @var InventoryRequestInterfaceFactory
     */
    private $inventoryRequestFactory;

    /**
     * @var SourceSelectionResultAdapterFactory
     */
    private $sourceSelectionResultAdapterFactory;

    public function __construct(
        GetDefaultSourceSelectionAlgorithmCodeInterface $getDefaultSourceSelectionAlgorithmCode,
        InventoryRequestInterfaceFactory $inventoryRequestFactory,
        SourceSelectionServiceInterface $sourceSelectionService,
        SourceSelectionResultAdapterFactory $sourceSelectionResultAdapterFactory
    ) {
        $this->getDefaultSourceSelectionAlgorithmCode = $getDefaultSourceSelectionAlgorithmCode;
        $this->sourceSelectionService = $sourceSelectionService;
        $this->inventoryRequestFactory = $inventoryRequestFactory;
        $this->sourceSelectionResultAdapterFactory = $sourceSelectionResultAdapterFactory;
    }

    /**
     * @param int $stockId
     * @param array $requestItems
     * @param null|string $algorithmCode
     *
     * @return SourceSelectionResultAdapter
     */
    public function create(
        int $stockId,
        array $requestItems,
        ?string $algorithmCode = null
    ): SourceSelectionResultAdapter {
        if (!$algorithmCode) {
            $algorithmCode = $this->getDefaultSourceSelectionAlgorithmCode->execute();
        }

        $inventoryRequest = $this->inventoryRequestFactory->create(
            [
                'stockId' => $stockId,
                'items'   => $requestItems
            ]
        );
        $sourceSelectionResult = $this->sourceSelectionService->execute(
            $inventoryRequest,
            $algorithmCode
        );

        return $this->sourceSelectionResultAdapterFactory->create(['sourceSelectionResult' => $sourceSelectionResult]);
    }
}
