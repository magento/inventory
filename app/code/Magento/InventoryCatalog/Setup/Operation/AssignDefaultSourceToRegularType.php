<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Setup\Operation;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryApi\Api\Data\SourceTypeLinkInterface;

/**
 * Assign default source to default stock
 */
class AssignDefaultSourceToRegularType
{
    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param ResourceConnection $resource
     */
    public function __construct(
        DefaultSourceProviderInterface $defaultSourceProvider,
        ResourceConnection $resource
    ) {
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->resource = $resource;
    }

    /**
     * Assign default source to stock
     *
     * @return void
     */
    public function execute()
    {
        $connection = $this->resource->getConnection();
        $stockSourceLinkData = [
            SourceTypeLinkInterface::SOURCE_CODE => $this->defaultSourceProvider->getCode(),
            SourceTypeLinkInterface::TYPE_CODE => SourceTypeLinkInterface::DEFAULT_SOURCE_TYPE
        ];
        $connection->insert($this->resource->getTableName('inventory_source_type_link'), $stockSourceLinkData);
    }
}
