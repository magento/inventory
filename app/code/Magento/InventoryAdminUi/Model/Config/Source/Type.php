<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Inventory\Model\ResourceModel\Source\Type as resourceModel;

/**
 * Class Type
 * @package Magento\InventoryAdminUi\Model\Config\Source
 */
class Type implements OptionSourceInterface
{
    /**
     * @var \Magento\Cms\Model\Page
     */
    protected $resourceModel;

    /**
     * Constructor
     *
     * @param \Magento\Cms\Model\Page $cmsPage
     */
    public function __construct(resourceModel $resourceModel)
    {
        $this->resourceModel = $resourceModel;
    }

    /**
     * Get available options
     *
     * @codeCoverageIgnore
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
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
