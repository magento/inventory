<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InventoryInStorePickup\Model\Carrier\Command\GetFreeBoxes;
use Magento\InventoryInStorePickup\Model\Carrier\Command\GetIsAnyPickupLocationAvailable;
use Magento\InventoryInStorePickupApi\Model\Carrier\GetShippingPriceInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;

/**
 * In-Store Pickup Delivery Method Model.
 */
class InStorePickup extends AbstractCarrier implements CarrierInterface
{
    private const CARRIER_CODE = 'in_store';
    private const METHOD_CODE = 'pickup';

    /**
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var ResultFactory
     */
    private $rateResultFactory;

    /**
     * @var MethodFactory
     */
    private $rateMethodFactory;

    /**
     * @var GetIsAnyPickupLocationAvailable
     */
    private $getIsAnyPickupLocationAvailable;

    /**
     * @var GetShippingPriceInterface
     */
    private $getShippingPrice;

    /**
     * @var GetFreeBoxes
     */
    private $getFreeBoxes;

    /**
     * InStorePickup constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param GetIsAnyPickupLocationAvailable $getIsAnyPickupLocationAvailable
     * @param GetFreeBoxes $getFreeBoxes
     * @param GetShippingPriceInterface $getShippingPrice
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        GetIsAnyPickupLocationAvailable $getIsAnyPickupLocationAvailable,
        GetFreeBoxes $getFreeBoxes,
        GetShippingPriceInterface $getShippingPrice,
        array $data = []
    ) {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->getIsAnyPickupLocationAvailable = $getIsAnyPickupLocationAvailable;
        $this->getFreeBoxes = $getFreeBoxes;
        $this->getShippingPrice = $getShippingPrice;
        $this->_code = self::CARRIER_CODE;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @inheritdoc
     */
    public function processAdditionalValidation(\Magento\Framework\DataObject $request)
    {
        /** @var \Magento\Quote\Model\Quote\Address\RateRequest $request */
        return $this->getIsAnyPickupLocationAvailable->execute((int)$request->getWebsiteId());
    }

    /**
     * @inheritdoc
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->isActive() || !$this->processAdditionalValidation($request)) {
            return null;
        }

        $freeBoxes = $this->getFreeBoxes->execute($request);
        $shippingPrice = $this->getFinalShippingPrice($request, $freeBoxes);

        $result = $this->rateResultFactory->create();

        if ($shippingPrice !== null) {
            $method = $this->createResultMethod($shippingPrice);
            $result->append($method);
        }

        return $result;
    }

    /**
     * @param float $shippingPrice
     *
     * @return Method
     */
    private function createResultMethod(float $shippingPrice): Method
    {
        $method = $this->rateMethodFactory->create(
            [
                'data' => [
                    'carrier' => self::CARRIER_CODE,
                    'carrier_title' => $this->getConfigData('title'),
                    'method' => self::METHOD_CODE,
                    'method_title' => $this->getConfigData('name'),
                    'cost' => $shippingPrice
                ]
            ]
        );

        $method->setPrice($shippingPrice);

        return $method;
    }

    /**
     * @inheritdoc
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @param float $freeBoxes
     *
     * @return float|null
     */
    private function getFinalShippingPrice(RateRequest $request, float $freeBoxes): ?float
    {
        $shippingPrice = null;
        $configPrice = (float)$this->getConfigData('price');
        $shippingPrice = $this->getShippingPrice->execute($request, $configPrice, $freeBoxes);

        $shippingPrice = $this->getFinalPriceWithHandlingFee($shippingPrice);

        if ($shippingPrice !== null && $request->getPackageQty() == $freeBoxes) {
            $shippingPrice = 0.0;
        }

        return $shippingPrice;
    }
}
