<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Downloadable\Api\Data\File\ContentInterfaceFactory;
use Magento\Downloadable\Api\Data\LinkInterfaceFactory;
use Magento\Downloadable\Api\Data\SampleInterfaceFactory;
use Magento\Downloadable\Helper\Download;
use Magento\Downloadable\Model\Link;
use Magento\Downloadable\Model\Product\Type;
use Magento\TestFramework\Helper\Bootstrap;

Bootstrap::getInstance()->getInstance()->reinitialize();
$objectManager = Bootstrap::getObjectManager();

$product = $objectManager->create(Product::class);
$sampleFactory = $objectManager->create(SampleInterfaceFactory::class);
$linkFactory = $objectManager->create(LinkInterfaceFactory::class);

$downloadableData = [
    'sample' => [
        [
            'is_delete' => 0,
            'sample_id' => 0,
            'title' => 'Downloadable Product Sample Title',
            'type' => Download::LINK_TYPE_FILE,
            'file' => json_encode(
                [
                    [
                        'file' => '/f/u/jellyfish_1_4.jpg',
                        'name' => 'jellyfish_1_4.jpg',
                        'size' => 1024,
                        'status' => 0,
                    ],
                ]
            ),
            'sample_url' => null,
            'sort_order' => '0',
        ],
    ],
];
$product->setTypeId(
    Type::TYPE_DOWNLOADABLE
)->setAttributeSetId(
    4
)->setWebsiteIds(
    [1]
)->setName(
    'Downloadable Product'
)->setUrlKey(
    'downloadable ' . time()
)->setSku(
    'downloadable-product'
)->setPrice(
    10
)->setVisibility(
    Visibility::VISIBILITY_BOTH
)->setStatus(
    Status::STATUS_ENABLED
)->save();

$extension = $product->getExtensionAttributes();
$links = [];
$linkData = [
    'product_id' => $product->getId(),
    'sort_order' => '0',
    'title' => 'Downloadable Product Link',
    'sample' => [
        'type' => Download::LINK_TYPE_FILE,
        'url' => null,
    ],
    'type' => Download::LINK_TYPE_FILE,
    'is_shareable' => Link::LINK_SHAREABLE_CONFIG,
    'link_url' => null,
    'is_delete' => 0,
    'number_of_downloads' => 15,
    'price' => 15.00,
];
$link = $linkFactory->create(['data' => $linkData]);
$link->setId(null);
$link->setSampleType($linkData['sample']['type']);

$content = $objectManager->create(ContentInterfaceFactory::class)->create();
$content->setFileData(
    base64_encode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . 'test_image.jpg'))
);
$content->setName('jellyfish_2_4.jpg');
$link->setLinkFileContent($content);

$sampleContent = $objectManager->create(ContentInterfaceFactory::class)->create();
$sampleContent->setFileData(
    base64_encode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . 'test_image.jpg'))
);
$sampleContent->setName('jellyfish_1_3.jpg');
$link->setSampleFileContent($sampleContent);
$link->setSampleUrl($linkData['sample']['url']);
$link->setLinkType($linkData['type']);
$link->setStoreId($product->getStoreId());
$link->setWebsiteId($product->getStore()->getWebsiteId());
$link->setProductWebsiteIds($product->getWebsiteIds());
if (!$link->getSortOrder()) {
    $link->setSortOrder(1);
}
if (null === $link->getPrice()) {
    $link->setPrice(0);
}
if ($link->getIsUnlimited()) {
    $link->setNumberOfDownloads(0);
}
$links[] = $link;

$extension->setDownloadableProductLinks($links);

if (isset($downloadableData['sample']) && is_array($downloadableData['sample'])) {
    $samples = [];
    foreach ($downloadableData['sample'] as $sampleData) {
        if (!$sampleData || (isset($sampleData['is_delete']) && (bool)$sampleData['is_delete'])) {
            continue;
        } else {
            unset($sampleData['sample_id']);
            $sample = $sampleFactory->create(['data' => $sampleData]);
            $sample->setId(null);
            $sample->setStoreId($product->getStoreId());
            $sample->setSampleType($sampleData['type']);
            $sample->setSampleUrl($sampleData['sample_url']);
            $content = $objectManager->create(
                ContentInterfaceFactory::class
            )->create();
            $content->setFileData(
                base64_encode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . 'test_image.jpg'))
            );
            $content->setName('jellyfish_1_4.jpg');
            $sample->setSampleFileContent($content);
            $sample->setSortOrder($sampleData['sort_order']);
            $samples[] = $sample;
        }
    }
    $extension->setDownloadableProductSamples($samples);
}
$product->setExtensionAttributes($extension);

if ($product->getLinksPurchasedSeparately()) {
    $product->setTypeHasRequiredOptions(true)->setRequiredOptions(true);
} else {
    $product->setTypeHasRequiredOptions(false)->setRequiredOptions(false);
}
$product->save();

$stockRegistry = $objectManager->get(StockRegistryInterface::class);
$stockItem = $stockRegistry->getStockItem($product->getId());
$stockItem->setUseConfigManageStock(true);
$stockItem->setQty(100);
$stockItem->setIsInStock(true);
$stockRegistry->updateStockItemBySku($product->getSku(), $stockItem);
