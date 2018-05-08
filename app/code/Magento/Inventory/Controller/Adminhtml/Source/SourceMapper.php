<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Controller\Adminhtml\Source;

use Magento\Framework\Api\ImmutableDataObjectHelper;
use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * Populate Source by data. Specified for form structure
 *
 * @api
 */
class SourceMapper
{
    /**
     * @var ImmutableDataObjectHelper
     */
    private $immutableDataObjectHelper;

    /**
     * @var SourceCarrierDataProcessor
     */
    private $sourceCarrierDataProcessor;

    /**
     * @var SourceRegionDataProcessor
     */
    private $sourceRegionDataProcessor;

    /**
     * @param ImmutableDataObjectHelper $immutableDataObjectHelper
     * @param SourceCarrierDataProcessor $sourceCarrierDataProcessor
     * @param SourceRegionDataProcessor $sourceRegionDataProcessor
     */
    public function __construct(
        ImmutableDataObjectHelper $immutableDataObjectHelper,
        SourceCarrierDataProcessor $sourceCarrierDataProcessor,
        SourceRegionDataProcessor $sourceRegionDataProcessor
    ) {
        $this->immutableDataObjectHelper = $immutableDataObjectHelper;
        $this->sourceCarrierDataProcessor = $sourceCarrierDataProcessor;
        $this->sourceRegionDataProcessor = $sourceRegionDataProcessor;
    }

    /**
     * @param SourceInterface $source
     * @param array $data
     *
     * @return SourceInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \ReflectionException
     */
    public function map(SourceInterface $source, array $data): SourceInterface
    {
        $data['general'] = $this->sourceCarrierDataProcessor->process($data['general']);
        $data['general'] = $this->sourceRegionDataProcessor->process($data['general']);

        $source = $this->immutableDataObjectHelper->mapFromArray(
            $source,
            $data['general'],
            SourceInterface::class
        );

        return $source;
    }
}
