<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryGraphQl\Test\Unit\Model\Resolver;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventoryGraphQl\Model\Resolver\StockStatus;
use Magento\InventoryGraphQl\Model\Resolver\StockStatusProvider;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;
use GraphQL\Executor\Promise\Adapter\SyncPromiseQueue;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StockStatusTest extends TestCase
{
    private const STOCK_ID = 2;

    /**
     * @var array<string, bool>
     */
    private array $salability = [
        'simple-in' => true,
        'simple-out' => false,
        'configurable-in' => true,
        'configurable-out' => false,
        'configurable-child-in' => true,
        'configurable-child-out' => false,
        'option-sku-in' => true,
        'option-sku-out' => false,
        'bundle-in' => true,
        'bundle-out' => false,
        'not-indexed-in' => true,
        'bundle-selections-in' => false,
        '123' => true,
        'ABC' => true,
        'abc' => true,
        'ghost' => false,
    ];

    /**
     * Whether the selections of a bundle intended to be bought can be bought
     *
     * @var array<string, bool>
     */
    private array $buyable = [
        'bundle-in' => true,
        'bundle-out' => false,
        'bundle-selections-in' => true,
    ];

    /**
     * SKUs a multi-SKU lookup leaves out of its result, as the stock index lookup does for SKUs without index rows
     *
     * @var string[]
     */
    private array $missingFromBatch = ['not-indexed-in', 'ghost', 'flaky'];

    /**
     * SKUs a single-SKU lookup returns no result for
     *
     * @var string[]
     */
    private array $withoutResult = ['ghost'];

    /**
     * SKUs a single-SKU lookup throws for
     *
     * @var string[]
     */
    private array $failingLookups = ['flaky'];

    /**
     * Whether a multi-SKU lookup throws
     *
     * @var bool
     */
    private bool $failMultiSkuLookup = false;

    /**
     * @var array<int, string[]>
     */
    private array $salabilityCalls = [];

    /**
     * @var Field
     */
    private Field $field;

    /**
     * @var ContextInterface
     */
    private ContextInterface $context;

    /**
     * @var ResolveInfo
     */
    private ResolveInfo $info;

    protected function setUp(): void
    {
        $this->field = $this->createStub(Field::class);
        $this->context = $this->createStub(ContextInterface::class);
        $this->info = $this->createStub(ResolveInfo::class);
    }

    public function testAllProductsOfABatchAreCheckedWithOneCall(): void
    {
        $getStockId = $this->createMock(GetStockIdForCurrentWebsite::class);
        $getStockId->expects($this->once())->method('execute')->willReturn(self::STOCK_ID);
        $areProductsSalable = $this->createMock(AreProductsSalableInterface::class);
        $areProductsSalable->expects($this->once())
            ->method('execute')
            ->with(['simple-in', 'simple-out', 'configurable-in', 'bundle-out'], self::STOCK_ID)
            ->willReturnCallback($this->salabilityCallback(...));
        $resolver = new StockStatus(
            $getStockId,
            $areProductsSalable,
            $this->createStub(StockStatusProvider::class),
            $this->valueFactory()
        );

        $requests = [
            $this->request($this->product('simple', 'simple-in')),
            $this->request($this->product('simple', 'simple-out')),
            $this->request($this->product('configurable', 'configurable-in')),
            $this->request($this->product('simple', 'simple-in')),
            $this->request($this->product(Type::TYPE_CODE, 'bundle-out')),
        ];
        $response = $resolver->resolve($this->context, $this->field, $requests);

        $this->assertSame(
            ['IN_STOCK', 'OUT_OF_STOCK', 'IN_STOCK', 'IN_STOCK', 'OUT_OF_STOCK'],
            array_map(fn (ResolveRequest $request) => $response->findResponseFor($request), $requests)
        );
    }

    public function testSkuMissingFromTheBatchResultIsCheckedOnItsOwn(): void
    {
        $resolver = new StockStatus(
            $this->stockIdStub(),
            $this->salabilityFake(),
            $this->createStub(StockStatusProvider::class),
            $this->valueFactory()
        );
        $requests = [
            $this->request($this->product('simple', 'simple-out')),
            $this->request($this->product('simple', 'not-indexed-in')),
        ];

        $response = $resolver->resolve($this->context, $this->field, $requests);

        $this->assertSame([['simple-out', 'not-indexed-in'], ['not-indexed-in']], $this->salabilityCalls);
        $this->assertSame('OUT_OF_STOCK', $response->findResponseFor($requests[0]));
        $this->assertSame('IN_STOCK', $response->findResponseFor($requests[1]));
    }

    public function testBatchOfBundlesToBeBoughtIsDelegatedWithoutASalabilityCheck(): void
    {
        $getStockId = $this->createMock(GetStockIdForCurrentWebsite::class);
        $getStockId->expects($this->never())->method('execute');
        $areProductsSalable = $this->createMock(AreProductsSalableInterface::class);
        $areProductsSalable->expects($this->never())->method('execute');
        $request = $this->request($this->product(Type::TYPE_CODE, 'bundle-in', [], true));
        $stockStatusProvider = $this->createMock(StockStatusProvider::class);
        $stockStatusProvider->expects($this->once())
            ->method('resolve')
            ->with($this->field, $this->context, $this->info, $request->getValue(), null)
            ->willReturn('IN_STOCK');
        $resolver = new StockStatus($getStockId, $areProductsSalable, $stockStatusProvider, $this->valueFactory());

        $response = $resolver->resolve($this->context, $this->field, [$request]);

        $this->assertSame('IN_STOCK', $response->findResponseFor($request));
    }

    public function testFailingItemsFailOnlyThemselves(): void
    {
        $failingProduct = $this->createStub(Product::class);
        $failingProduct->method('getSku')->willThrowException(new \RuntimeException('Broken product'));
        $requests = [
            $this->request($this->product('simple', 'simple-in')),
            $this->createRequest(['sku' => 'simple-in']),
            $this->createRequest(['model' => $this->product('simple', 'simple-in', ['option'])]),
            $this->request($failingProduct),
            $this->request($this->product('simple', 'ghost')),
            $this->request($this->product('simple', 'simple-out')),
        ];

        $response = $this->resolverWithSingleProductResolver()->resolve($this->context, $this->field, $requests);

        $this->assertSame('IN_STOCK', $response->findResponseFor($requests[0]));
        $this->assertSame('OUT_OF_STOCK', $response->findResponseFor($requests[5]));
        $missingModel = $this->rejectionOf($response->findResponseFor($requests[1]));
        $this->assertInstanceOf(LocalizedException::class, $missingModel);
        $this->assertSame('"model" value should be specified', $missingModel->getMessage());
        $this->assertStringContainsString(
            'Undefined array key "sku"',
            $this->rejectionOf($response->findResponseFor($requests[2]))->getMessage()
        );
        $this->assertSame('Broken product', $this->rejectionOf($response->findResponseFor($requests[3]))->getMessage());
        $this->assertInstanceOf(\Error::class, $this->rejectionOf($response->findResponseFor($requests[4])));
        $this->assertSame(
            [['simple-in', 'ghost', 'simple-out'], ['ghost'], ['ghost']],
            $this->salabilityCalls
        );
    }

    public function testFailedMultiSkuLookupSendsEveryItemToTheSingleResolver(): void
    {
        $this->failMultiSkuLookup = true;
        $requests = [
            $this->request($this->product('simple', 'simple-in')),
            $this->request($this->product('simple', 'simple-out')),
        ];

        $response = $this->resolverWithSingleProductResolver()->resolve($this->context, $this->field, $requests);

        $this->assertSame([['simple-in', 'simple-out'], ['simple-in'], ['simple-out']], $this->salabilityCalls);
        $this->assertSame('IN_STOCK', $response->findResponseFor($requests[0]));
        $this->assertSame('OUT_OF_STOCK', $response->findResponseFor($requests[1]));
    }

    public function testFailedStockIdLookupFailsEveryItemWithItsOwnError(): void
    {
        $getStockId = $this->createStub(GetStockIdForCurrentWebsite::class);
        $getStockId->method('execute')->willThrowException(new \RuntimeException('No stock'));
        $resolver = new StockStatus(
            $getStockId,
            $this->salabilityFake(),
            new StockStatusProvider($getStockId, $this->salabilityFake(), $this->createStub(Type::class)),
            $this->valueFactory()
        );
        $requests = [
            $this->request($this->product('simple', 'simple-in')),
            $this->request($this->product('simple', 'simple-out')),
        ];

        $response = $resolver->resolve($this->context, $this->field, $requests);

        $this->assertSame([], $this->salabilityCalls);
        $this->assertSame('No stock', $this->rejectionOf($response->findResponseFor($requests[0]))->getMessage());
        $this->assertSame('No stock', $this->rejectionOf($response->findResponseFor($requests[1]))->getMessage());
    }

    public function testFailedSingleSkuLookupFailsOnlyThatItemAndRunsOncePerSku(): void
    {
        $requests = [
            $this->request($this->product('simple', 'simple-in')),
            $this->request($this->product('simple', 'flaky')),
            $this->request($this->product('simple', 'flaky')),
        ];

        $response = $this->resolverWithSingleProductResolver()->resolve($this->context, $this->field, $requests);

        $this->assertSame('IN_STOCK', $response->findResponseFor($requests[0]));
        $this->assertSame(
            'Lookup failed for flaky',
            $this->rejectionOf($response->findResponseFor($requests[1]))->getMessage()
        );
        $this->assertSame(
            'Lookup failed for flaky',
            $this->rejectionOf($response->findResponseFor($requests[2]))->getMessage()
        );
        // One single-SKU lookup by the batch, then one per item by the single product resolver.
        $this->assertSame([['simple-in', 'flaky'], ['flaky'], ['flaky'], ['flaky']], $this->salabilityCalls);
    }

    public function testIntLikeSkuIsMatched(): void
    {
        $requests = [
            $this->request($this->product('simple', '123')),
            $this->request($this->product('simple', 'simple-out')),
        ];

        $response = $this->resolverWithSingleProductResolver()->resolve($this->context, $this->field, $requests);

        $this->assertSame([['123', 'simple-out']], $this->salabilityCalls);
        $this->assertSame('IN_STOCK', $response->findResponseFor($requests[0]));
        $this->assertSame('OUT_OF_STOCK', $response->findResponseFor($requests[1]));
    }

    public function testSkusDifferingOnlyByCaseAreBothResolved(): void
    {
        $requests = [
            $this->request($this->product('simple', 'ABC')),
            $this->request($this->product('simple', 'abc')),
        ];

        $response = $this->resolverWithSingleProductResolver()->resolve($this->context, $this->field, $requests);

        $this->assertSame([['ABC', 'abc']], $this->salabilityCalls);
        $this->assertSame('IN_STOCK', $response->findResponseFor($requests[0]));
        $this->assertSame('IN_STOCK', $response->findResponseFor($requests[1]));
    }

    public function testResultSkuInAnotherCaseFallsBackToTheSingleSkuLookup(): void
    {
        $areProductsSalable = $this->createMock(AreProductsSalableInterface::class);
        $areProductsSalable->expects($this->exactly(2))
            ->method('execute')
            ->willReturnMap([
                [['Simple-In', 'simple-out'], self::STOCK_ID, [
                    $this->salabilityResult('simple-in', true),
                    $this->salabilityResult('simple-out', false),
                ]],
                [['Simple-In'], self::STOCK_ID, [$this->salabilityResult('simple-in', true)]],
            ]);
        $resolver = new StockStatus(
            $this->stockIdStub(),
            $areProductsSalable,
            $this->createStub(StockStatusProvider::class),
            $this->valueFactory()
        );
        $requests = [
            $this->request($this->product('simple', 'Simple-In')),
            $this->request($this->product('simple', 'simple-out')),
        ];

        $response = $resolver->resolve($this->context, $this->field, $requests);

        $this->assertSame('IN_STOCK', $response->findResponseFor($requests[0]));
        $this->assertSame('OUT_OF_STOCK', $response->findResponseFor($requests[1]));
    }

    public function testEveryProductResolvesAsTheSingleProductResolverDoes(): void
    {
        $bundleType = $this->createStub(Type::class);
        $bundleType->method('checkProductBuyState')->willReturnCallback(
            function (Product $product) {
                if (!$this->buyable[$product->getSku()]) {
                    throw new LocalizedException(__('Not salable'));
                }
            }
        );
        $stockStatusProvider = new StockStatusProvider($this->stockIdStub(), $this->salabilityFake(), $bundleType);
        $resolver = new StockStatus(
            $this->stockIdStub(),
            $this->salabilityFake(),
            $stockStatusProvider,
            $this->valueFactory()
        );

        $values = [
            'simple in stock' => ['model' => $this->product('simple', 'simple-in')],
            'simple out of stock' => ['model' => $this->product('simple', 'simple-out')],
            'configurable in stock' => ['model' => $this->product('configurable', 'configurable-in')],
            'configurable out of stock' => ['model' => $this->product('configurable', 'configurable-out')],
            'configurable item, child in stock' => [
                'model' => $this->product('configurable', 'configurable-child-in', ['option']),
                'sku' => 'configurable-child-in',
            ],
            'configurable item, child out of stock' => [
                'model' => $this->product('configurable', 'configurable-child-out', ['option']),
                'sku' => 'configurable-child-out',
            ],
            'product with options, in stock' => [
                'model' => $this->product('simple', 'simple-out', ['option']),
                'sku' => 'option-sku-in',
            ],
            'product with options, out of stock' => [
                'model' => $this->product('simple', 'simple-in', ['option']),
                'sku' => 'option-sku-out',
            ],
            'bundle in stock' => ['model' => $this->product(Type::TYPE_CODE, 'bundle-in')],
            'bundle out of stock' => ['model' => $this->product(Type::TYPE_CODE, 'bundle-out')],
            'bundle to be bought, in stock' => ['model' => $this->product(Type::TYPE_CODE, 'bundle-in', [], true)],
            'bundle to be bought, out of stock' => [
                'model' => $this->product(Type::TYPE_CODE, 'bundle-out', [], true),
            ],
            'bundle to be bought, selections in stock' => [
                'model' => $this->product(Type::TYPE_CODE, 'bundle-selections-in', [], true),
            ],
            'not in the stock index' => ['model' => $this->product('simple', 'not-indexed-in')],
        ];

        $expected = [];
        foreach ($values as $case => $value) {
            $expected[$case] = $stockStatusProvider->resolve($this->field, $this->context, $this->info, $value);
        }
        $requests = array_map(fn (array $value) => $this->createRequest($value), $values);
        $response = $resolver->resolve($this->context, $this->field, array_values($requests));
        $actual = array_map(fn (ResolveRequest $request) => $response->findResponseFor($request), $requests);

        $this->assertSame($expected, $actual);
        $this->assertSame(
            [
                'simple in stock' => 'IN_STOCK',
                'simple out of stock' => 'OUT_OF_STOCK',
                'configurable in stock' => 'IN_STOCK',
                'configurable out of stock' => 'OUT_OF_STOCK',
                'configurable item, child in stock' => 'IN_STOCK',
                'configurable item, child out of stock' => 'OUT_OF_STOCK',
                'product with options, in stock' => 'IN_STOCK',
                'product with options, out of stock' => 'OUT_OF_STOCK',
                'bundle in stock' => 'IN_STOCK',
                'bundle out of stock' => 'OUT_OF_STOCK',
                'bundle to be bought, in stock' => 'IN_STOCK',
                'bundle to be bought, out of stock' => 'OUT_OF_STOCK',
                'bundle to be bought, selections in stock' => 'IN_STOCK',
                'not in the stock index' => 'IN_STOCK',
            ],
            $actual
        );
    }

    /**
     * @param string[] $skus
     * @param int $stockId
     * @return IsProductSalableResultInterface[]
     */
    private function salabilityCallback(array $skus, int $stockId): array
    {
        $this->assertSame(self::STOCK_ID, $stockId);
        $this->salabilityCalls[] = $skus;
        if (count($skus) > 1 && $this->failMultiSkuLookup) {
            throw new \RuntimeException('Multi-SKU lookup failed');
        }
        if (count($skus) === 1 && in_array(current($skus), $this->failingLookups, true)) {
            throw new \RuntimeException('Lookup failed for ' . current($skus));
        }
        $results = [];
        foreach ($skus as $sku) {
            if (count($skus) > 1 && in_array($sku, $this->missingFromBatch, true)) {
                continue;
            }
            if (in_array($sku, $this->withoutResult, true)) {
                continue;
            }
            $results[] = $this->salabilityResult($sku, $this->salability[$sku]);
        }

        return $results;
    }

    private function salabilityResult(string $sku, bool $isSalable): IsProductSalableResultInterface
    {
        $result = $this->createStub(IsProductSalableResultInterface::class);
        $result->method('getSku')->willReturn($sku);
        $result->method('isSalable')->willReturn($isSalable);

        return $result;
    }

    private function resolverWithSingleProductResolver(): StockStatus
    {
        $stockStatusProvider = new StockStatusProvider(
            $this->stockIdStub(),
            $this->salabilityFake(),
            $this->createStub(Type::class)
        );

        return new StockStatus(
            $this->stockIdStub(),
            $this->salabilityFake(),
            $stockStatusProvider,
            $this->valueFactory()
        );
    }

    private function valueFactory(): ValueFactory
    {
        $valueFactory = $this->createStub(ValueFactory::class);
        $valueFactory->method('create')->willReturnCallback(fn (callable $callback) => new Value($callback));

        return $valueFactory;
    }

    private function rejectionOf(mixed $value): ?\Throwable
    {
        $this->assertInstanceOf(Value::class, $value);
        $reason = null;
        $value->then(null, function (\Throwable $e) use (&$reason) {
            $reason = $e;
        });
        SyncPromiseQueue::run();

        return $reason;
    }

    private function salabilityFake(): AreProductsSalableInterface
    {
        $areProductsSalable = $this->createStub(AreProductsSalableInterface::class);
        $areProductsSalable->method('execute')->willReturnCallback($this->salabilityCallback(...));

        return $areProductsSalable;
    }

    private function stockIdStub(): GetStockIdForCurrentWebsite
    {
        $getStockId = $this->createStub(GetStockIdForCurrentWebsite::class);
        $getStockId->method('execute')->willReturn(self::STOCK_ID);

        return $getStockId;
    }

    /**
     * @param string $typeId
     * @param string $sku
     * @param array $options
     * @param bool $toBeBought
     * @return Product
     */
    private function product(string $typeId, string $sku, array $options = [], bool $toBeBought = false): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('getTypeId')->willReturn($typeId);
        $product->method('getSku')->willReturn($sku);
        $product->method('getOptions')->willReturn($options);
        $product->method('getCustomOption')
            ->willReturnMap([['info_buyRequest', $toBeBought ? new DataObject() : null]]);

        return $product;
    }

    private function request(Product $product): ResolveRequest
    {
        return $this->createRequest(['model' => $product]);
    }

    private function createRequest(array $value): ResolveRequest
    {
        return new ResolveRequest($this->field, $this->context, $this->info, $value, null);
    }
}
