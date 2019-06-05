<?php
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model;

class Product
{
    /**
     * @var \Magento\InventorySalesApi\Model\GetAssignedStockIdForWebsiteInterface
     */
    protected $getAssignedStockIdForWebsite;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\InventorySalesApi\Api\IsProductSalableInterface
     */
    protected $isProductSalable;

    /**
     * Product constructor.
     * @param \Magento\InventorySalesApi\Model\GetAssignedStockIdForWebsiteInterface $getAssignedStockIdForWebsite
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\InventorySalesApi\Api\IsProductSalableInterface $isProductSalable
     */
    public function __construct(
        \Magento\InventorySalesApi\Model\GetAssignedStockIdForWebsiteInterface $getAssignedStockIdForWebsite,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\InventorySalesApi\Api\IsProductSalableInterface $isProductSalable
    ){
        $this->getAssignedStockIdForWebsite = $getAssignedStockIdForWebsite;
        $this->storeManager                 = $storeManager;
        $this->isProductSalable             = $isProductSalable;
    }
    /**
     * @param \Magento\Catalog\Model\Product $subject
     * @param bool $result
     */
    public function afterIsSalable(
        \Magento\Catalog\Model\Product $subject,
        bool $result
    ){
        $currentWebsite = $this->storeManager->getWebsite();
        if($currentWebsite){
            $result = $this->isProductSalable->execute(
                $subject->getSku(),
                $this->getAssignedStockIdForWebsite->execute($currentWebsite->getCode())
            );
        }

        return $result;
    }
}