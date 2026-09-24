<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Resolve the stored `inventory_source.source_code` values matching the given codes case-insensitively
 */
class GetCanonicalSourceCodes
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Map lowercased source codes to the canonical, as stored, source codes.
     *
     * @param string[] $sourceCodes
     * @return string[]
     */
    public function execute(array $sourceCodes): array
    {
        if (!$sourceCodes) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(Source::TABLE_NAME_SOURCE);

        $select = $connection->select()
            ->from($tableName, ['source_code'])
            ->where('source_code IN (?)', $sourceCodes);

        $storedSourceCodes = $connection->fetchCol($select);

        $canonicalSourceCodes = [];
        foreach ($storedSourceCodes as $storedSourceCode) {
            $canonicalSourceCodes[mb_strtolower($storedSourceCode)] = $storedSourceCode;
        }

        return $canonicalSourceCodes;
    }
}
