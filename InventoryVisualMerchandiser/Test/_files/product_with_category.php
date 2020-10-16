<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Setup\CategorySetup;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Quote\Model\ResourceModel\Quote\Item as QuoteItem;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

$category = Bootstrap::getObjectManager()->create(Category::class);
$category->isObjectNew(true);
$category->setId(
    1234
)->setCreatedAt(
    '2020-01-01 09:00:00'
)->setName(
    'Category 1'
)->setParentId(
    2
)->setPath(
    '1/2/333'
)->setLevel(
    2
)->setAvailableSortBy(
    ['position', 'name']
)->setDefaultSortBy(
    'name'
)->setIsActive(
    true
)->setPosition(
    1
)->save();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);

/** @var $installer CategorySetup */
$installer = Bootstrap::getObjectManager()->create(CategorySetup::class);

/** @var Website $website */
$website = Bootstrap::getObjectManager()->create(Website::class);
$website->load('us_website', 'code');
$websiteIds = [$website->getId()];

$attributeSetId = $installer->getAttributeSetId('catalog_product', 'Default');
$productId = 10;

/** @var $product Product */
$product = Bootstrap::getObjectManager()->create(Product::class);
$product->setTypeId(Type::TYPE_SIMPLE)
    ->setId($productId)
    ->setAttributeSetId($attributeSetId)
    ->setWebsiteIds($websiteIds)
    ->setName('Simple product ' . $productId)
    ->setSku('simple_' . $productId)
    ->setPrice($productId)
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setCategoryIds([1234])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1]);

$product = $productRepository->save($product);

/** @var Item $stockItem */
$stockItem = Bootstrap::getObjectManager()->create(Item::class);
$stockItem->load($productId, 'product_id');

if (!$stockItem->getProductId()) {
    $stockItem->setProductId($productId);
}
$stockItem->setUseConfigManageStock(1);
$stockItem->setQty(100);
$stockItem->setIsQtyDecimal(0);
$stockItem->setIsInStock(1);
$stockItem->save();

// Remove any previously created product with the same id.
try {
    $productToDelete = $productRepository->getById(1);
    $productRepository->delete($productToDelete);

    /** @var QuoteItem $itemResource */
    $itemResource = Bootstrap::getObjectManager()->get(QuoteItem::class);
    $itemResource->getConnection()->delete(
        $itemResource->getMainTable(),
        'product_id = ' . $productToDelete->getId()
    );
} catch (\Exception $e) {
    // Nothing to remove
}
