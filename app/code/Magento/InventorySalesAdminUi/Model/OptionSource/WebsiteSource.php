<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Model\OptionSource;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory;

/**
 * @inheritDoc
 *
 * @api
 */
class WebsiteSource implements OptionSourceInterface
{
    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var CollectionFactory
     */
    private $websiteCollectionFactory;

    /**
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param CollectionFactory|null $websiteCollection
     */
    public function __construct(
        WebsiteRepositoryInterface $websiteRepository,
        CollectionFactory $websiteCollection = null
    ) {
        $this->websiteRepository = $websiteRepository;
        $this->websiteCollectionFactory = $websiteCollection ?: ObjectManager::getInstance()
            ->get(CollectionFactory::class);
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $websites = [];
        $websiteCollection = $this->websiteCollectionFactory->create();
        foreach ($websiteCollection->getItems() as $website) {
            if ($website->getCode() === WebsiteInterface::ADMIN_CODE) {
                continue;
            }
            $websites[] = [
                'value' => $website->getCode(),
                'label' => $website->getName(),
            ];
        }
        return $websites;
    }
}
