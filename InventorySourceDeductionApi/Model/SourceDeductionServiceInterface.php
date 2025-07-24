<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySourceDeductionApi\Model;

/**
 * Process source deduction
 *
 * @api
 */
interface SourceDeductionServiceInterface
{
    /**
     * Executes source deduction process using the provided request, ensuring inventory adjustments are applied.
     *
     * @param SourceDeductionRequestInterface $sourceDeductionRequest
     * @return void
     */
    public function execute(SourceDeductionRequestInterface $sourceDeductionRequest): void;
}
