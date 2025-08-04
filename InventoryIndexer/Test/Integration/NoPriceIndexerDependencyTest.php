<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
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
