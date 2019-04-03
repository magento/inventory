<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration\Extension;

use Magento\Framework\ObjectManagerInterface;
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
            'default' => false,
            'eu-1' => true,
            'eu-2' => true,
            'eu-3' => false,
            'eu-disabled' => false,
            'us-1' => true,
        ];

        $searchResult = $this->sourceRepository->getList();

        /** @var \Magento\InventoryApi\Api\Data\SourceInterface $item */
        foreach ($searchResult->getItems() as $item) {
            $item->getExtensionAttributes()->setIsPickupLocationActive(
                $pickupLocationConfig[$item->getSourceCode()]
            );
            $this->sourceRepository->save($item);
        }

        $searchResult = $this->sourceRepository->getList();

        $pickupLocationsStatus = [];

        foreach ($searchResult->getItems() as $item) {
            $pickupLocationsStatus[$item->getSourceCode()] = $item->getExtensionAttributes()->getIsPickupLocationActive();
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
        $source->getExtensionAttributes()->setIsPickupLocationActive(true);
        $this->sourceRepository->save($source);

        $source = $this->sourceRepository->get($sourceCode);
        $this->assertEquals(true, $source->getExtensionAttributes()->getIsPickupLocationActive());
    }
}
