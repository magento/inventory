<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\GraphQl\InventoryInStorePickupQuote;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventoryInStorePickupApi\Model\GetPickupLocationInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\Quote\Test\Fixture\QuoteIdMask as QuoteMaskFixture;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test coverage of extension of Quote Graph Ql.
 * Test possibility to pass Pickup Location Code to Shipping Address.
 */
class PickupLocationForShippingTest extends GraphQlAbstract
{
    /**
     * @var GetPickupLocationInterface
     */
    private $getPickupLocation;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    public function setUp(): void
    {
        $this->getPickupLocation = Bootstrap::getObjectManager()->get(GetPickupLocationInterface::class);
        $this->storeManager = Bootstrap::getObjectManager()->get(StoreManagerInterface::class)->getStore();
        $this->sourceRepository = Bootstrap::getObjectManager()->get(SourceRepositoryInterface::class);
        $this->fixtures = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage();
    }

    /**
     * @throws NoSuchEntityException
     * @throws ValidationException
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    #[
        DbIsolation(false),
        AppIsolation(true),
        DataFixture(
            SourceFixture::class,
            [
                'latitude'=>38.7634,
                'longitude'=>-95.84,
                'postcode' => '66413',
                'city' => 'Burlingame',
                'region' => 'California',
                'region_id' => 12,
                'street' => 'Bloomquist Dr 100',
                'contact_name' => 'Test Store',
                'phone' => '9876543210',
                'enabled' => 1
            ],
            as: 'src1'
        ),
        DataFixture(StockFixture::class, as: 'stk2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [
                ['stock_id' => '$stk2.stock_id$', 'source_code' => '$src1.source_code$'],
            ]
        ),
        DataFixture(StockSalesChannelsFixture::class, ['stock_id' => '$stk2.stock_id$', 'sales_channels' => ['base']]),
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$p1.sku$', 'source_code' => '$src1.source_code$', 'quantity' => 1],
            ]
        ),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$p1.id$']),
        DataFixture(QuoteMaskFixture::class, ['cart_id' => '$cart.id$'], 'quoteIdMask'),
    ]
    public function testSetPickupLocationForShippingAddress()
    {
        $source = $this->fixtures->get('src1');
        $source->getExtensionAttributes()->setIsPickupLocationActive(1);
        $this->sourceRepository->save($source);
        $pickupLocationCode = $source->getSourceCode();

        $response = $this->graphQlMutation($this->getSetShippingAddressOnCartMutation(
            $this->fixtures->get('quoteIdMask')->getMaskedId(),
            $pickupLocationCode
        ));

        $this->assertNewShippingAddressFields(
            current($response['setShippingAddressesOnCart']['cart']['shipping_addresses']),
            $pickupLocationCode
        );
    }

    /**
     * Get setShippingAddressOnCart mutation with pickupLocationCode
     *
     * @param string $cartId
     * @param string $pickupLocationCode
     * @return string
     */
    private function getSetShippingAddressOnCartMutation(string $cartId, string $pickupLocationCode): string
    {
        return <<<MUTATION
            mutation {
              setShippingAddressesOnCart(
                input: {
                  cart_id: "{$cartId}"
                  shipping_addresses: [
                    {
                      pickup_location_code: "{$pickupLocationCode}"
                    }
                  ]
                }
              ) {
                cart {
                  shipping_addresses {
                    firstname
                    street
                    city
                    postcode
                    telephone
                    country {
                      code
                      label
                    },
                    pickup_location_code,
                    __typename
                  }
                }
              }
            }
        MUTATION;
    }

    /**
     * @param array $shippingAddressResponse
     * @param string $pickupLocationCode
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function assertNewShippingAddressFields(array $shippingAddressResponse, string $pickupLocationCode)
    {
        $pickupLocation = $this->getPickupLocation->execute(
            $pickupLocationCode,
            SalesChannelInterface::TYPE_WEBSITE,
            $this->storeManager->getWebsite()->getCode()
        );
        $assertionMap = [
            ['response_field' => 'firstname', 'expected_value' => $pickupLocation->getName()],
            ['response_field' => 'street', 'expected_value' => [0 => $pickupLocation->getStreet()]],
            ['response_field' => 'city', 'expected_value' => $pickupLocation->getCity()],
            ['response_field' => 'postcode', 'expected_value' => $pickupLocation->getPostcode()],
            ['response_field' => 'telephone', 'expected_value' => '9876543210'],
            [
                'response_field' => 'country',
                'expected_value' => [
                    'code' => $pickupLocation->getCountryId(),
                    'label' => $pickupLocation->getCountryId()
                ]
            ],
            ['response_field' => 'pickup_location_code', 'expected_value' => $pickupLocation->getPickupLocationCode()],
            ['response_field' => '__typename', 'expected_value' => 'ShippingCartAddress']
        ];
        $this->assertResponseFields($shippingAddressResponse, $assertionMap);
    }
}
