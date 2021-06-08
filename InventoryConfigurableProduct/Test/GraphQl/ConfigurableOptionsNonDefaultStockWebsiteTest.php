<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\GraphQl;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Api\Data\OptionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Check configurable product options and its children products when non default stock assigned to different website
 */
class ConfigurableOptionsNonDefaultStockWebsiteTest extends GraphQlAbstract
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->storeManager = $objectManager->get(StoreManagerInterface::class)->getStore();
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/product_configurable_in_us_stock.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDbIsolation disabled
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Exception
     */
    public function testConfigurableProductOptionsAndVariantsTest()
    {
        $defaultWesiteStore = 'store_for_us_website';
        $this->storeManager->setCurrentStore($defaultWesiteStore);
        $productSku = 'configurable';
        $query = <<<QUERY
{
  products(filter: { sku: { eq: "$productSku" } }) {
    items {
      id
      attribute_set_id
      name
      sku
      ... on ConfigurableProduct {
        configurable_options {
          id
          attribute_id_v2
          label
          position
          use_default
          attribute_code
          values {
            value_index
            label
          }
          product_id
        }
        variants {
          product {
            id
            name
            sku
            attribute_set_id
          }
          attributes {
            uid
            label
            code
            value_index
          }
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', ['Store' => $defaultWesiteStore]);
        $configurableProduct = $this->productRepository->get($productSku, false, null, true);
        $this->assertConfigurableProductOptions($configurableProduct, $response);
    }

    /**
     * @param ProductInterface $product
     * @param array $response
     */
    private function assertConfigurableProductOptions($product, $response)
    {
        $this->assertNotEmpty($response['products']['items'][0]);
        $productResponce = $response['products']['items'][0];
        /** @var OptionInterface $configurableOption */
        $configurableOption = $product->getExtensionAttributes()->getConfigurableProductOptions()[0];
        $this->assertResponseFields(
            $productResponce,
            [
                'id' => $product->getId(),
                'sku' => $product->getSku(),
            ]
        );
        $this->assertResponseFields(
            $productResponce['configurable_options'][0],
            [
                'attribute_id_v2' => $configurableOption->getAttributeId(),
                'label' => $configurableOption->getLabel(),
            ]
        );
        $i = 0;
        foreach ($configurableOption->getOptions() as $option) {
            $this->assertResponseFields(
                $productResponce['configurable_options'][0]['values'][$i++],
                [
                    'label' => $option['label'],
                    'value_index' => $option['value_index'],
                ]
            );
        }
        $i = 0;
        foreach ($configurableOption->getOptions() as $option) {
            $this->assertResponseFields(
                $productResponce['variants'][$i++]['attributes'][0],
                [
                    'label' => $option['label'],
                    'value_index' => $option['value_index'],
                ]
            );
        }
    }
}
