<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Import\Source\Item\Importer;

use Magento\CatalogImportExport\Model\Import\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;
use Magento\Inventory\Model\SourceItemFactory;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

class CustomSourceProcessor
{
    /**
     * Source Item Interface Factory
     *
     * @var SourceItemFactory $sourceItemFactory
     */
    private $sourceItemFactory;

    /**
     * Source Repository Interface
     *
     * @var SourceRepositoryInterface $sourceRepositoryInterface
     */
    private $sourceRepositoryInterface;

    /**
     * StockItemImporter constructor
     *
     * @param SourceItemFactory $sourceItemFactory
     * @param SourceRepositoryInterface $sourceRepositoryInterface
     */
    public function __construct(
        SourceItemFactory $sourceItemFactory,
        SourceRepositoryInterface $sourceRepositoryInterface
    ) {
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceRepositoryInterface = $sourceRepositoryInterface;
    }

    /**
     * @param array $data
     * @return SourceItemInterface|bool
     */
    public function execute(array $data)
    {
        /** @var SourceInterface $source */
        if ($source = $this->getSource($data)) {
            $inStock = (isset($data['is_in_stock'])) ? $data['is_in_stock'] : 0;
            $sourceItem = $this->sourceItemFactory->create();
            $sourceItem->setSku($data[Product::COL_SKU]);
            $sourceItem->setSourceId($this->getSource($data['qty']));
            $sourceItem->setQuantity($this->getQty($data['qty']));
            $sourceItem->setStatus($inStock);
            return $sourceItem;
        }
        return false;
    }

    /**
     * Return Source ID if source exists with given id
     *
     * @param $qty
     * @return bool|SourceInterface
     * @throws ValidationException
     */
    private function getSource($qty)
    {
        $parts = explode('=', $qty);
        $sourceId = $parts[0];
        try {
            /** @var SourceInterface $source */
            $source = $this->sourceRepositoryInterface->get($sourceId);
            if ($source->getSourceId()) {
                return $source;
            }
        } catch (NoSuchEntityException $e) {
            throw new ValidationException(__('Source with Id %d does not exist for column qty, some row number', $sourceId));
        }
        return false;
    }

    /**
     * Return qty from value passed
     *
     * @param $qty
     * @return int|string
     */
    private function getQty($qty)
    {
        $parts = explode('=', $qty);
        if (is_numeric($parts[1])) {
            return $parts[1];
        }
        return 0;
    }
}
