<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Command\Input;

/**
 * Fetches standard input for cli commands and retrieves as array
 */
class GetCommandlineStandardInput
{
    /**
     * Fetches and filters standard input lines from the CLI, trims whitespace, and returns them as a non-empty array.
     *
     * @return array
     */
    public function execute(): array
    {
        $values = [];
        //phpcs:ignore Magento2.Functions.DiscouragedFunction
        $handle = fopen('php://stdin', 'r');
        if ($handle) {
            //phpcs:ignore Magento2.Functions.DiscouragedFunction
            while ($line = fgets($handle)) {
                $values[] = trim($line);
            }
            //phpcs:ignore Magento2.Functions.DiscouragedFunction
            fclose($handle);
        }

        return array_filter($values);
    }
}
