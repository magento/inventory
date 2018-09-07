<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;

/**
 * Get minimal sale qty system value, considering customer group.
 */
class GetSystemMinSaleQty
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var JsonValidator
     */
    private $jsonValidator;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param JsonValidator $jsonValidator
     * @param Json $serializer
     * @param Session $session
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        JsonValidator $jsonValidator,
        Json $serializer,
        Session $session
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->jsonValidator = $jsonValidator;
        $this->serializer = $serializer;
        $this->session = $session;
    }

    /**
     * @return float
     */
    public function execute(): float
    {
        $result = 1;
        $minSaleQtyData = $this->scopeConfig->getValue(StockItemConfigurationInterface::XML_PATH_MIN_SALE_QTY);
        if (is_string($minSaleQtyData) && $this->jsonValidator->isValid($minSaleQtyData)) {
            $unserializedMinSaleQty = $this->serializer->unserialize($minSaleQtyData);
            $customerGroupId = $this->session->getCustomerGroupId();
            if (is_array($unserializedMinSaleQty)) {
                foreach ($unserializedMinSaleQty as $goupId => $qty) {
                    if ($goupId === $customerGroupId) {
                        $result = $qty;
                        break;
                    }
                }
            } else {
                $result = $unserializedMinSaleQty;
            }
        }

        return $result;
    }
}
