<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 08.02.18
 * Time: 23:54
 */

namespace Magento\InventoryConfiguration\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\CatalogInventory\Model\Configuration;
use Magento\CatalogInventory\Model\Stock\StockItemRepository as LegacyStockItemRepository;
use Magento\CatalogInventory\Model\Stock\Item as LegacyStockItem;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;

class StockConfigurationValidator implements StockConfigurationInterface
{
    /**
     * @var StockConfigurationInterface[]
     */
    protected $validators;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $stockItemCriteriaFactory;

    /**
     * @var LegacyStockItemRepository
     */
    protected $legacyStockItemRepository;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var ProductResourceModel
     */
    protected $productResource;

    /**
     * StockValidationComposer constructor.
     * @param StockConfigurationInterface[] $validators
     * @param Configuration $configuration
     * @param LegacyStockItemRepository $legacyStockItemRepository
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory
     * @param ProductResourceModel $productResource
     * @throws LocalizedException
     */
    public function __construct(
        array $validators=[],
        Configuration $configuration,
        LegacyStockItemRepository $legacyStockItemRepository,
        StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory,
        ProductResourceModel $productResource
    ) {
        $this->configuration = $configuration;
        $this->legacyStockItemRepository = $legacyStockItemRepository;
        $this->productResource = $productResource;
        $this->stockItemCriteriaFactory = $stockItemCriteriaFactory;

        foreach ($validators as $validator) {
            if (!$validator instanceof StockConfigurationInterface) {
                throw new LocalizedException(
                    __('Validator must implement StockConfigurationInterface.')
                );
            }
        }
        $this->validators = $validators;
    }

    /**
     * Old validation logic
     *
     * @param $sku
     * @param $stockId
     * @param $qtyWithReservation
     * @param $isSalable
     * @return bool
     * @throws LocalizedException
     */
    public function validate($sku, $stockId, $qtyWithReservation, $isSalable): bool
    {
        //old validation logic below
        $globalMinQty = $this->configuration->getMinQty();
        $legacyStockItem = $this->getLegacyStockItem($sku);
        if (null === $legacyStockItem) {
            return false;
        }
        if ($this->isManageStock($legacyStockItem)) {
            if (($legacyStockItem->getUseConfigMinQty() == 1 && $qtyWithReservation <= $globalMinQty)
                || ($legacyStockItem->getUseConfigMinQty() == 0 && $qtyWithReservation <= $legacyStockItem->getMinQty())
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param LegacyStockItem $legacyStockItem
     *
     * @return bool
     */
    private function isManageStock(LegacyStockItem $legacyStockItem): bool
    {
        $globalManageStock = $this->configuration->getManageStock();
        $manageStock = false;
        if (($legacyStockItem->getUseConfigManageStock() == 1 && $globalManageStock == 1)
            || ($legacyStockItem->getUseConfigManageStock() == 0 && $legacyStockItem->getManageStock() == 1)
        ) {
            $manageStock = true;
        }
        return $manageStock;
    }

    /**
     * @param string $sku
     * @return StockItemInterface|mixed|null
     * @throws LocalizedException
     */
    private function getLegacyStockItem(string $sku)
    {
        $productIds = $this->productResource->getProductsIdsBySkus([$sku]);
        $searchCriteria = $this->stockItemCriteriaFactory->create();
        $searchCriteria->addFilter(StockItemInterface::PRODUCT_ID, StockItemInterface::PRODUCT_ID, $productIds[$sku]);
        $legacyStockItem = $this->legacyStockItemRepository->getList($searchCriteria);
        $items = $legacyStockItem->getItems();
        return count($items) ? reset($items) : null;
    }
}
