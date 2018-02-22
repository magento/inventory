<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Plugin\Import;

use Magento\Catalog\Model\ProductRepository;
use Magento\CatalogImportExport\Model\StockItemImporterInterface;
use Magento\Inventory\Model\IsSourceItemsManagementAllowedForProductType;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalog\Api\DefaultSourceProviderInterface;

class SourceItemImporter
{
    /**
     * Source Items Save Interface for saving multiple source items
     *
     * @var SourceItemsSaveInterface $sourceItemsSave
     */
    private $sourceItemsSave;

    /**
     * Source Item Interface Factory
     *
     * @var SourceItemInterfaceFactory $sourceItemFactory
     */
    private $sourceItemFactory;

    /**
     * Default Source Provider
     *
     * @var DefaultSourceProviderInterface $defaultSource
     */
    private $defaultSource;

    /**
     * @var IsSourceItemsManagementAllowedForProductType
     */
    private $isSourceItemsManagementAllowedForProductType;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param IsSourceItemsManagementAllowedForProductType $isSourceItemsManagementAllowedForProductType
     * @param ProductRepository $productRepository
     */
    public function __construct(
        SourceItemsSaveInterface $sourceItemsSave,
        SourceItemInterfaceFactory $sourceItemFactory,
        DefaultSourceProviderInterface $defaultSourceProvider,
        IsSourceItemsManagementAllowedForProductType $isSourceItemsManagementAllowedForProductType,
        ProductRepository $productRepository
    ) {
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->defaultSource = $defaultSourceProvider;
        $this->isSourceItemsManagementAllowedForProductType = $isSourceItemsManagementAllowedForProductType;
        $this->productRepository = $productRepository;
    }

    /**
     * After plugin Import to import Stock Data to Source Items
     *
     * @param StockItemImporterInterface $subject
     * @param null $result
     * @param array $stockData
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     * @return void
     * @see StockItemImporterInterface::import()
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterImport(
        StockItemImporterInterface $subject,
        $result,
        array $stockData
    ) {
        $sourceItems = [];
        foreach ($stockData as $stockDatum) {
            if ($this->isNeedToSkip($stockDatum)) {
                continue;
            }

            $inStock = (isset($stockDatum['is_in_stock'])) ? intval($stockDatum['is_in_stock']) : 0;
            $qty = (isset($stockDatum['qty'])) ? $stockDatum['qty'] : 0;
            /** @var SourceItemInterface $sourceItem */
            $sourceItem = $this->sourceItemFactory->create();
            $sourceItem->setSku($stockDatum['sku']);
            $sourceItem->setSourceCode($this->defaultSource->getCode());
            $sourceItem->setQuantity($qty);
            $sourceItem->setStatus($inStock);
            $sourceItems[] = $sourceItem;
        }
        if (count($sourceItems) > 0) {
            /** SourceItemInterface[] $sourceItems */
            $this->sourceItemsSave->execute($sourceItems);
        }
    }

    /**
     * @param array $stockDatum
     *
     * @return bool
     */
    private function isNeedToSkip(array $stockDatum): bool
    {
        $needToSkip = false;
        if (!isset($stockDatum['sku'])) {
           $needToSkip = true;
        }

        if (!$needToSkip && isset($stockDatum['product_id'])) {
            $product = $this->productRepository->getById($stockDatum['product_id']);
            $typeId = $product->getTypeId();
            $needToSkip = !$this->isSourceItemsManagementAllowedForProductType->execute($typeId);
        }

        return $needToSkip;
    }
}
