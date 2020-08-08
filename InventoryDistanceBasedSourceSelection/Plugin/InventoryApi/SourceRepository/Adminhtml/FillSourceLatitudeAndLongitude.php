<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDistanceBasedSourceSelection\Plugin\InventoryApi\SourceRepository\Adminhtml;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryDistanceBasedSourceSelection\Model\DistanceProvider\GetLatLngFromSource;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
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
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * ComputeSourceLatitudeAndLongitude constructor.
     *
     * @param GetLatLngFromSource $getLatLngFromSource
     * @param LoggerInterface $logger
     * @param ManagerInterface $messageManager
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        GetLatLngFromSource $getLatLngFromSource,
        LoggerInterface $logger,
        ManagerInterface $messageManager
    ) {
        $this->getLatLngFromSource = $getLatLngFromSource;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
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
            } catch (LocalizedException $exception) {
                $this->logger->error($exception);
                $this->messageManager->addWarningMessage($exception->getMessage());
            } catch (\Exception $exception) {
                $this->logger->error($exception);
                $this->messageManager->addWarningMessage(__('Failed to geocode the source address'));
            }
        }

        return [$source];
    }
}
