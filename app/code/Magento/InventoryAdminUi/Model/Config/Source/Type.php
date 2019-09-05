<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Inventory\Model\ResourceModel\Source\Type as SourceTypeResource;

/**
 * Class Type
 * @package Magento\InventoryAdminUi\Model\Config\Source
 */
class Type implements OptionSourceInterface
{
    /**
     * @var SourceTypeResource
     */
    private $resourceModel;

    /**
     * @param SourceTypeResource $resourceModel
     */
    public function __construct(SourceTypeResource $resourceModel)
    {
        $this->resourceModel = $resourceModel;
    }

    /**
     * Get available options
     *
     * @codeCoverageIgnore
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray()
    {
        $allTypes = $this->resourceModel->getAllTypes();

        $types = array();
        foreach ($allTypes as $key => $type) {
            $types[$key]['value'] = $type['type_code'];
            $types[$key]['label'] = $type['name'];
        }

        return $types;
    }
}
