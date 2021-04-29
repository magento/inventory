<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryQuoteGraphQl\Test\GraphQl;

use Exception;
use Magento\Config\Model\ResourceModel\Config\Data;
use Magento\Framework\ObjectManagerInterface;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * GraphQl tests for "Only x left" with different stock and website combinations.
 */
class MergeCartsTest extends GraphQlAbstract
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var QuoteResource
     */
    private $quoteResource;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $quoteIdToMaskedId;

    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->quoteResource = $this->objectManager->get(QuoteResource::class);
        $this->quoteFactory = $this->objectManager->get(QuoteFactory::class);
        $this->quoteIdToMaskedId = $this->objectManager->get(QuoteIdToMaskedQuoteIdInterface::class);
        $this->customerTokenService = $this->objectManager->get(CustomerTokenServiceInterface::class);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/products_new.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryShipping/Test/_files/source_items_for_simple_on_multi_source.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryQuoteGraphQl/Test/_files/quote_with_simple_product_saved.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryQuoteGraphQl/Test/_files/add_simple_product.php
     */
    public function testMergeGuestWithCustomerCartWithOutOfStockQuantity()
    {
        $this->assignWebsiteToStock(10, 'base');
        $customerQuote = $this->quoteFactory->create();
        $this->quoteResource->load($customerQuote, 'test_quote', 'reserved_order_id');

        $guestQuote = $this->quoteFactory->create();
        $this->quoteResource->load(
            $guestQuote,
            'test_order_with_simple_product_without_address',
            'reserved_order_id'
        );

        $customerQuoteMaskedId = $this->quoteIdToMaskedId->execute((int)$customerQuote->getId());
        $guestQuoteMaskedId = $this->quoteIdToMaskedId->execute((int)$guestQuote->getId());

        $query = $this->getCartMergeMutation($guestQuoteMaskedId, $customerQuoteMaskedId);
        $mergeResponse = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        self::assertArrayHasKey('mergeCarts', $mergeResponse);
        $cartResponse = $mergeResponse['mergeCarts'];
        self::assertArrayHasKey('items', $cartResponse);
        self::assertCount(1, $cartResponse['items']);
        $cartResponse = $this->graphQlMutation(
            $this->getCartQuery($customerQuoteMaskedId),
            [],
            '',
            $this->getHeaderMap()
        );

        self::assertArrayHasKey('cart', $cartResponse);
        self::assertArrayHasKey('items', $cartResponse['cart']);
        self::assertCount(1, $cartResponse['cart']['items']);
        $item1 = $cartResponse['cart']['items'][0];
        self::assertArrayHasKey('quantity', $item1);
        self::assertEquals(14, $item1['quantity']);
    }

    /**
     * @param string $username
     * @param string $password
     * @return array
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    private function getHeaderMap(string $username = 'customer@example.com', string $password = 'password'): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($username, $password);
        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];
        return $headerMap;
    }

    /**
     * Get cart query
     *
     * @param string $maskedId
     * @return string
     */
    private function getCartQuery(string $maskedId): string
    {
        return <<<QUERY
{
  cart(cart_id: "{$maskedId}") {
    items {
      quantity
      product {
        sku
      }
    }
  }
}
QUERY;
    }

    /**
     * Create the mergeCart mutation
     *
     * @param string $guestQuoteMaskedId
     * @param string $customerQuoteMaskedId
     * @return string
     */
    private function getCartMergeMutation(string $guestQuoteMaskedId, string $customerQuoteMaskedId): string
    {
        return <<<QUERY
mutation {
  mergeCarts(
    source_cart_id: "{$guestQuoteMaskedId}"
    destination_cart_id: "{$customerQuoteMaskedId}"
  ){
  items {
      quantity
      product {
        sku
      }
    }
  }
}
QUERY;
    }

    /**
     * Assign website to stock as sales channel.
     *
     * @param int $stockId
     * @param string $websiteCode
     * @return void
     */
    private function assignWebsiteToStock(int $stockId, string $websiteCode): void
    {
        $stockRepository = Bootstrap::getObjectManager()->get(StockRepositoryInterface::class);
        $salesChannelFactory = Bootstrap::getObjectManager()->get(SalesChannelInterfaceFactory::class);
        $stock = $stockRepository->get($stockId);
        $extensionAttributes = $stock->getExtensionAttributes();
        $salesChannels = $extensionAttributes->getSalesChannels();

        $salesChannel = $salesChannelFactory->create();
        $salesChannel->setCode($websiteCode);
        $salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
        $salesChannels[] = $salesChannel;

        $extensionAttributes->setSalesChannels($salesChannels);
        $stockRepository->save($stock);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $resource = $this->objectManager->get(Data::class);
        $resource->clearScopeData('default', 0);
    }
}
