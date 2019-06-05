<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration\Extension;

use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class InventorySourceExtensionTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var SourceRepositoryInterface */
    private $sourceRepository;

    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->sourceRepository = $this->objectManager->get(SourceRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     */
    public function testGetListOfSourcesWithPickupLocationExtensionAfterSave()
    {
        $pickupLocationConfig = [
            'default' => ['active' => false, 'name' => 'default', 'desc' => 'default'],
            'eu-1' => ['active' => true, 'name' => '', 'desc' => ''],
            'eu-2' => ['active' => true, 'name' => 'zzz', 'desc' => ''],
            'eu-3' => ['active' => false, 'name' => '', 'desc' => 'zzz1'],
            'eu-disabled' => ['active' => false, 'name' => '', 'desc' => ''],
            'us-1' => ['active' => true, 'name' => '666', 'desc' => ''],
        ];

        $searchResult = $this->sourceRepository->getList();

        /** @var SourceInterface $item */
        foreach ($searchResult->getItems() as $item) {
            $item->getExtensionAttributes()
                 ->setIsPickupLocationActive($pickupLocationConfig[$item->getSourceCode()]['active'])
                 ->setFrontendDescription($pickupLocationConfig[$item->getSourceCode()]['desc'])
                 ->setFrontendName($pickupLocationConfig[$item->getSourceCode()]['name']);
            $this->sourceRepository->save($item);
        }

        $searchResult = $this->sourceRepository->getList();

        $pickupLocationsStatus = [];

        foreach ($searchResult->getItems() as $item) {
            $extension = $item->getExtensionAttributes();
            $pickupLocationsStatus[$item->getSourceCode()]['active'] = $extension->getIsPickupLocationActive();
            $pickupLocationsStatus[$item->getSourceCode()]['name'] = $extension->getFrontendName();
            $pickupLocationsStatus[$item->getSourceCode()]['desc'] = $extension->getFrontendDescription();
        }

        $this->assertEquals($pickupLocationConfig, $pickupLocationsStatus);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source.php
     */
    public function testGetSourceWithPickupLocationExtensionAfterSave()
    {
        $sourceCode = 'source-code-1';

        $source = $this->sourceRepository->get($sourceCode);
        $source->getExtensionAttributes()
               ->setIsPickupLocationActive(true)
               ->setFrontendName('zzz')
               ->setFrontendDescription('666');
        $this->sourceRepository->save($source);

        $source = $this->sourceRepository->get($sourceCode);
        $this->assertEquals(true, $source->getExtensionAttributes()->getIsPickupLocationActive());
        $this->assertEquals('zzz', $source->getExtensionAttributes()->getFrontendName());
        $this->assertEquals('666', $source->getExtensionAttributes()->getFrontendDescription());
    }
}
