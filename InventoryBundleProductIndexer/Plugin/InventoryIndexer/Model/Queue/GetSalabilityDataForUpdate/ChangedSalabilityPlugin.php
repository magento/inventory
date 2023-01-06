<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\InventoryIndexer\Model\Queue\GetSalabilityDataForUpdate;

use Magento\Bundle\Model\ResourceModel\Selection;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryIndexer\Model\Queue\GetSalabilityDataForUpdate;
use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\InventoryIndexer\Model\Queue\ReservationDataFactory;

class ChangedSalabilityPlugin
{
    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var Selection
     */
    private $selectionResource;

    /**
     * @var ReservationDataFactory
     */
    private $reservationDataFactory;

    /**
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param Selection $selectionResource
     * @param ReservationDataFactory $reservationDataFactory
     */
    public function __construct(
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        Selection $selectionResource,
        ReservationDataFactory $reservationDataFactory
    ) {
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->selectionResource = $selectionResource;
        $this->reservationDataFactory = $reservationDataFactory;
    }

    /**
     * Change bundle products salability depending on changed children products salability
     *
     * @param GetSalabilityDataForUpdate $subject
     * @param array $result
     * @param ReservationData $reservationData
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(GetSalabilityDataForUpdate $subject, array $result, ReservationData $reservationData)
    {
        if (empty($result)) {
            return $result;
        }

        $childIds = $this->getProductIdsBySkus->execute(array_keys($result));
        $bundleIds = $this->selectionResource->getParentIdsByChild($childIds);
        if (empty($bundleIds)) {
            return $result;
        }

        $bundleSkus = $this->getSkusByProductIds->execute($bundleIds);
        $bundleReservationData = $this->reservationDataFactory->create([
            'skus' => $bundleSkus,
            'stock' => $reservationData->getStock(),
        ]);
        $bundleChangedSalability = $subject->execute($bundleReservationData);

        return $result + $bundleChangedSalability;
    }
}
