<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetBackorderStatusConfigurationValueInterface;

class GetBackorderStatusConfigurationValue implements GetBackorderStatusConfigurationValueInterface
{
    /**
     * @var ResourceConnection;
     */
    private $resourceConnection;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ResourceConnection $resourceConnection,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param string $sku
     * @param string $sourceCode
     * @return ?int
     */
    public function forSourceItem(string $sku, string $sourceCode): ?int
    {
        $connection = $this->resourceConnection->getConnection();
        $configurationTable = $this->resourceConnection->getTableName('inventory_configuration');
        $select = $connection->select()
            ->from($configurationTable)
            ->where('sku = ?', $sku)
            ->where('source_code = ?', $sourceCode)
            ->where('config_option = ?', SourceItemConfigurationInterface::BACKORDERS);
        $data = $connection->fetchRow($select);
        if ($data === false || $data['value'] === null) {
            return $this->forSource($sourceCode);
        }
        return (int)$data['value'];
    }

    /**
     * @param string $sourceCode
     * @return ?int
     */
    public function forSource(string $sourceCode): ?int
    {
        $connection = $this->resourceConnection->getConnection();
        $configurationTable = $this->resourceConnection->getTableName('inventory_configuration');
        $select = $connection->select()
            ->from($configurationTable)
            ->where('sku IS NULL')
            ->where('source_code = ?', $sourceCode)
            ->where('config_option = ?', SourceItemConfigurationInterface::BACKORDERS);
        $data = $connection->fetchRow($select);
        if ($data === false || $data['value'] === null) {
            return $this->forGlobal();
        }
        return (int)$data['value'];
    }

    /**
     * @param string $sourceCode
     * @return int
     */
    public function forGlobal(): int
    {
        // FIXME if config value is missing, null casted to int gives 0, that's a different semantic from missing value
        return (int)$this->scopeConfig->getValue(
            SourceItemConfigurationInterface::XML_PATH_BACKORDERS
        );
    }
}
