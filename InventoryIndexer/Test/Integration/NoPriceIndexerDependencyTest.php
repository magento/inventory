<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Integration;

use Magento\Framework\Indexer\Config\DependencyInfoProviderInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexer;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class NoPriceIndexerDependencyTest extends TestCase
{
    /**
     * @var DependencyInfoProviderInterface
     */
    private $dependencyInfoProvider;

    protected function setUp(): void
    {
        $this->dependencyInfoProvider = Bootstrap::getObjectManager()->get(DependencyInfoProviderInterface::class);
    }

    public function testPriceDependency()
    {
        $output = $this->dependencyInfoProvider->getIndexerIdsToRunBefore(PriceIndexer::INDEXER_ID);
        $this->assertArrayNotHasKey(InventoryIndexer::INDEXER_ID, array_values($output));
    }
}
