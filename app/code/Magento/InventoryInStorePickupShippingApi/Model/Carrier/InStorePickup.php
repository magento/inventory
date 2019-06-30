<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingApi\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\Command\GetFreePackagesInterface;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\Command\GetShippingPriceInterface;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\Command\GetShippingPriceRequestInterface;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\Validation\RequestValidatorInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\Error;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;

/**
 * In-Store Pickup Delivery Method Model.
 *
 * @api
 */
class InStorePickup extends AbstractCarrier implements CarrierInterface
{
    private const CARRIER_CODE = 'in_store';
    private const METHOD_CODE  = 'pickup';
    public const DELIVERY_METHOD = self::CARRIER_CODE . '_' . self::METHOD_CODE;

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
     * @var GetShippingPriceInterface
     */
    private $getShippingPrice;

    /**
     * @var RequestValidatorInterface
     */
    private $requestValidator;

    /**
     * @var GetFreePackagesInterface
     */
    private $getFreePackages;

    /**
     * @var GetShippingPriceRequestInterface
     */
    private $getShippingPriceRequest;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param GetShippingPriceInterface $getShippingPrice
     * @param RequestValidatorInterface $requestValidator
     * @param GetFreePackagesInterface $getFreePackages
     * @param GetShippingPriceRequestInterface $getShippingPriceRequest
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        GetShippingPriceInterface $getShippingPrice,
        RequestValidatorInterface $requestValidator,
        GetFreePackagesInterface $getFreePackages,
        GetShippingPriceRequestInterface $getShippingPriceRequest,
        array $data = []
    ) {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->getShippingPrice = $getShippingPrice;
        $this->requestValidator = $requestValidator;
        $this->getFreePackages = $getFreePackages;
        $this->getShippingPriceRequest = $getShippingPriceRequest;

        $this->_code = self::CARRIER_CODE;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @inheritdoc
     */
    public function processAdditionalValidation(\Magento\Framework\DataObject $request)
    {
        /** @var RateRequest $request */
        $validationResult = $this->requestValidator->validate($request);

        if (!$validationResult->isValid()) {
            $this->debug($validationResult->getErrors());

            return $this->createErrorResult();
        }

        return $validationResult->isValid();
    }

    /**
     * @return Error
     */
    private function createErrorResult(): Error
    {
        return $this->_rateErrorFactory->create(
            [
                'data' => [
                    'error_message' => $this->getConfigData('specificerrmsg')
                ]
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->isActive()) {
            return null;
        }

        $freeBoxes = $this->getFreePackages->execute($request);
        $shippingPriceRequest = $this->getShippingPriceRequest->execute(
            $request,
            (float)$this->getConfigData('price'),
            $freeBoxes
        );

        $shippingPrice = $this->getShippingPrice->execute($shippingPriceRequest, $request);

        $result = $this->rateResultFactory->create();

        $method = $this->createResultMethod($shippingPrice);
        $result->append($method);

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
}
