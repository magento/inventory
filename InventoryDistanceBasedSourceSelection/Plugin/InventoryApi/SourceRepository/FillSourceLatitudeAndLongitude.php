<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDistanceBasedSourceSelection\Plugin\InventoryApi\SourceRepository;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryDistanceBasedSourceSelection\Model\DistanceProvider\GetLatLngFromSource;
use Psr\Log\LoggerInterface;

/**
 * Compute latitude and longitude for a source if none is defined
 */
class FillSourceLatitudeAndLongitude
{
    /**
     * @var GetLatLngFromSource
     */
    private $getLatLngFromSource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ComputeSourceLatitudeAndLongitude constructor.
     *
     * @param GetLatLngFromSource $getLatLngFromSource
     * @param LoggerInterface $logger
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        GetLatLngFromSource $getLatLngFromSource,
        LoggerInterface $logger
    ) {
        $this->getLatLngFromSource = $getLatLngFromSource;
        $this->logger = $logger;
    }

    /**
     * Calculate latitude and longitude using google map if api key is defined
     *
     * @param SourceRepositoryInterface $subject
     * @param SourceInterface $source
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        SourceRepositoryInterface $subject,
        SourceInterface $source
    ): array {
        if (!$source->getLatitude() && !$source->getLongitude()) {
            try {
                $latLng = $this->getLatLngFromSource->execute($source);

                $source->setLatitude($latLng->getLat());
                $source->setLongitude($latLng->getLng());
            } catch (\Exception $exception) {
                $this->logger->error($exception);
            }
        }

        return [$source];
    }
}
