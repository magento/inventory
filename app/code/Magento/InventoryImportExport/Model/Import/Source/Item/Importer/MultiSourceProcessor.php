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
use Magento\Inventory\Model\SourceItem;
use Magento\Inventory\Model\SourceItemFactory;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryCatalog\Api\DefaultSourceProviderInterface;

class MultiSourceProcessor
{
    /**
     * Default Source Provider
     *
     * @var DefaultSourceProviderInterface $defaultSourceProvider
     */
    private $defaultSourceProvider;

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
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        SourceItemFactory $sourceItemFactory,
        SourceRepositoryInterface $sourceRepositoryInterface,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceRepositoryInterface = $sourceRepositoryInterface;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * @param array $data
     * @return SourceItemInterface[]|bool
     */
    public function execute(array $data)
    {
        if ($splitSourceData = $this->getSplitSourceData($data['qty'])) {
            $sourceItems = [];
            /** @var array $splitSourceDatum */
            foreach ($splitSourceData as $splitSourceDatum) {
                $inStock = (isset($data['is_in_stock'])) ? $data['is_in_stock'] : 0;
                /** @var SourceItem $sourceItem */
                $sourceItem = $this->sourceItemFactory->create();
                $sourceItem->setSku($data[Product::COL_SKU]);
                $sourceItem->setSourceId($splitSourceDatum['source']);
                $sourceItem->setQuantity($splitSourceDatum['qty']);
                $sourceItem->setStatus($inStock);
            }
            return $sourceItems;
        }
        return false;
    }

    /**
     * Return Source Data foreach qty value
     *
     * @param $qty
     * @return array|bool
     */
    public function getSplitSourceData($qty)
    {
        if (strpos($qty, '|') !== false) {
            $sourceData = [];
            $sources = explode('|', $qty);
            foreach ($sources as $source) {
                $individualSourceData = explode('=', $source);
                if ($individualSourceData[0] == 'default') {
                    $sourceId = $this->defaultSourceProvider->getId();
                } else {
                    $sourceId = $this->getSource($individualSourceData[0])->getSourceId();
                }
                $sourceData[] = [
                    'source' => $sourceId,
                    'qty' => $individualSourceData[1]
                ];
            }
            return $sourceData;
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
}
