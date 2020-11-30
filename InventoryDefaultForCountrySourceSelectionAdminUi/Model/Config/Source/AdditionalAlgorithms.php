<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDefaultForCountrySourceSelectionAdminUi\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\InventoryDefaultForCountrySourceSelection\Model\Algorithms\DefaultForCountryAlgorithm;
use Magento\InventorySourceSelectionApi\Api\GetSourceSelectionAlgorithmListInterface;

/**
 * System config source with available additional algorithms.
 */
class AdditionalAlgorithms implements OptionSourceInterface
{

    /**
     * @var GetSourceSelectionAlgorithmListInterface
     */
    private $getSourceSelectionAlgorithmList;

    /**
     * AdditionalAlgorithms constructor.
     *
     * @param GetSourceSelectionAlgorithmListInterface $getSourceSelectionAlgorithmList
     */
    public function __construct(
        GetSourceSelectionAlgorithmListInterface $getSourceSelectionAlgorithmList
    ) {
        $this->getSourceSelectionAlgorithmList = $getSourceSelectionAlgorithmList;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => '',
                'label' => '',
            ],
        ];
        $algorithms = $this->getSourceSelectionAlgorithmList->execute();
        foreach ($algorithms as $algorithm) {
            if ($algorithm->getCode() === DefaultForCountryAlgorithm::CODE) {
                continue;
            }
            $options[] = [
                'value' => $algorithm->getCode(),
                'label' => $algorithm->getTitle(),
            ];
        }
        return $options;
    }
}
