<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Model;

use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryIndexer\Model\GetProductsIdsToProcess;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use PHPUnit\Framework\TestCase;

class GetProductsIdsToProcessTest extends TestCase
{
    /**
     * @var GetProductsIdsToProcess
     */
    private GetProductsIdsToProcess $model;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $getProductIdsBySkus = $this->createMock(GetProductIdsBySkusInterface::class);
        $getProductIdsBySkus
            ->expects($this->any())->method('execute')->willReturnCallback(function ($skus) {
                $result = [];

                foreach ($skus as $sku) {
                    $result[$sku] = 1;
                }

                return $result;
            });
        $defaultStockProvider = $this->createMock(DefaultStockProviderInterface::class);
        $defaultStockProvider->expects($this->any())->method('getId')->willReturn(1);
        $this->model = new GetProductsIdsToProcess($getProductIdsBySkus, $defaultStockProvider);
    }

    /**
     * @param array $before
     * @param array $after
     * @param bool $force
     * @param array $expectedResult
     * @return void
     *
     * @dataProvider dataProvider
     */
    public function testExecute(array $before, array $after, bool $force, array $expectedResult): void
    {
        $actualResult = $this->model->execute($before, $after, $force);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * Data provider for execute
     *
     * @return array[]
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProvider(): array
    {
        return [
            'test with no difference' => [
                'before' => [
                    'sku1' => [
                        3 => true,
                    ],
                    'sku2' => [
                        5 => false
                    ]
                ],
                'after' => [
                    'sku1' => [
                        3 => true,
                    ],
                    'sku2' => [
                        5 => false
                    ]
                ],
                'force' => false,
                'expectedResult' => []
            ],

            'test adding to stock' => [
                'before' => [
                    'sku1' => [
                        3 => true,
                    ],
                ],
                'after' => [
                    'sku1' => [
                        3 => true,
                        5 => true
                    ],
                ],
                'force' => false,
                'expectedResult' => [
                    'sku1' => 1
                ]
            ],
            'test removing from stock' => [
                'before' => [
                    'sku1' => [
                        3 => true,
                        5 => true
                    ],
                ],
                'after' => [
                    'sku1' => [
                        3 => true,
                    ],
                ],
                'force' => false,
                'expectedResult' => [
                    'sku1' => 1
                ]
            ],
            'test turn out of stock' => [
                'before' => [
                    'sku1' => [
                        3 => true,
                    ],
                ],
                'after' => [
                    'sku1' => [
                        3 => false,
                    ],
                ],
                'force' => false,
                'expectedResult' => [
                    'sku1' => 1
                ]
            ],
            'test turn in stock' => [
                'before' => [
                    'sku1' => [
                        3 => false,
                    ],
                ],
                'after' => [
                    'sku1' => [
                        3 => true,
                    ],
                ],
                'force' => false,
                'expectedResult' => [
                    'sku1' => 1
                ]
            ],
            'test adding sku' => [
                'before' => [
                ],
                'after' => [
                    'sku1' => [
                        3 => true,
                    ],
                ],
                'force' => false,
                'expectedResult' => [
                    'sku1' => 1
                ]
            ],
            'test removing sku' => [
                'before' => [
                    'sku1' => [
                        3 => true,
                    ],
                ],
                'after' => [
                ],
                'force' => false,
                'expectedResult' => [
                    'sku1' => 1
                ]
            ],
            'test default stock force' => [
                'before' => [
                    'sku1' => [
                        1 => true,
                    ],
                ],
                'after' => [
                    'sku1' => [
                        1 => true,
                    ],
                ],
                'force' => true,
                'expectedResult' => [
                    'sku1' => 1
                ]
            ],
            'test default stock no force' => [
                'before' => [
                    'sku1' => [
                        1 => true,
                    ],
                ],
                'after' => [
                    'sku1' => [
                        1 => true,
                    ],
                ],
                'force' => false,
                'expectedResult' => []
            ]
        ];
    }
}
