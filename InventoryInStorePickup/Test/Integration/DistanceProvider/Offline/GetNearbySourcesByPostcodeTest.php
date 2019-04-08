<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration\DistanceProvider\Offline;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickup\Model\DistanceProvider\Offline\GetNearbySourcesByPostcode;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GetNearbySourcesByPostcodeTest extends TestCase
{
    /**
     * @var GetNearbySourcesByPostcode
     */
    private $getNearbySourcesByPostcode;

    protected function setUp()
    {
        $this->getNearbySourcesByPostcode = Bootstrap::getObjectManager()->get(GetNearbySourcesByPostcode::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_addresses.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/inventory_geoname.php
     *
     * @param string $country
     * @param string $postcode
     * @param int $radius
     * @param array $sortedSourceCodes
     *
     * @dataProvider executeDataProvider
     *
     * @magentoDbIsolation disabled
     * @throws
     */
    public function testExecute(string $country, string $postcode, int $radius, array $sortedSourceCodes)
    {
        /** @var SourceInterface[] $sources */
        $sources = $this->getNearbySourcesByPostcode->execute($country, $postcode, $radius);

        $this->assertCount(count($sortedSourceCodes), $sources);
        foreach ($sortedSourceCodes as $key => $code) {
            $this->assertEquals($code, $sources[$key]->getSourceCode());
        }
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            ['DE', '81671', 500,  ['eu-3']],
            ['FR', '56290', 1000, ['eu-1', 'eu-2']],
            ['FR', '84490', 1000, ['eu-2', 'eu-1', 'eu-3']],
            ['IT', '12022', 350,  ['eu-2']],
            ['IT', '39030', 350,  ['eu-3']],
            ['DE', '26419', 750,  ['eu-1', 'eu-3']],
        ];
    }
}
