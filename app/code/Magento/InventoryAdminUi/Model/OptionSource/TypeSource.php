<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Model\OptionSource;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Inventory\Model\ResourceModel\TypeSource\Collection;
use Magento\Inventory\Model\ResourceModel\TypeSource\CollectionFactory;

/**
 * Provide option values for UI
 */
class TypeSource implements OptionSourceInterface
{

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * SourceType constructor.
     *
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        return $collection->toOptionArray();
    }
}
