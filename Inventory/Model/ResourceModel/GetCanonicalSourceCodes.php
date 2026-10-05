<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

/**
 * Resolve the stored `inventory_source.source_code` values matching the given codes case-insensitively
 */
class GetCanonicalSourceCodes implements ResetAfterRequestInterface
{
    /**
     * @var string[]
     */
    private array $canonicalSourceCodes = [];

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Map lowercased source codes to the canonical, as stored, source codes.
     *
     * Source codes cannot be changed or deleted once created, so resolved codes are kept for the process lifetime.
     *
     * @param string[] $sourceCodes
     * @return string[]
     */
    public function execute(array $sourceCodes): array
    {
        $requestedSourceCodes = [];
        foreach ($sourceCodes as $sourceCode) {
            $requestedSourceCodes[mb_strtolower((string) $sourceCode)] = (string) $sourceCode;
        }

        $unresolvedSourceCodes = array_diff_key($requestedSourceCodes, $this->canonicalSourceCodes);
        if ($unresolvedSourceCodes) {
            $this->resolve(array_values($unresolvedSourceCodes));
        }

        return array_intersect_key($this->canonicalSourceCodes, $requestedSourceCodes);
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->canonicalSourceCodes = [];
    }

    /**
     * Load the stored source codes matching the given codes.
     *
     * @param string[] $sourceCodes
     * @return void
     */
    private function resolve(array $sourceCodes): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(Source::TABLE_NAME_SOURCE);

        $select = $connection->select()
            ->from($tableName, ['source_code'])
            ->where('source_code IN (?)', $sourceCodes);

        foreach ($connection->fetchCol($select) as $storedSourceCode) {
            $this->canonicalSourceCodes[mb_strtolower($storedSourceCode)] = $storedSourceCode;
        }
    }
}
