<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingApi\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\Command\GetShippingPriceInterface;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\Validation\RequestValidatorInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\Error;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;

/**
 * In-Store Pickup Delivery Method Model.
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InStorePickup extends AbstractCarrier implements CarrierInterface
{
    private const CARRIER_CODE = 'instore';
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
     * @var GetCarrierTitle
     */
    private $getCarrierTitle;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param GetShippingPriceInterface $getShippingPrice
     * @param RequestValidatorInterface $requestValidator
     * @param GetCarrierTitle $getCarrierTitle
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        GetShippingPriceInterface $getShippingPrice,
        RequestValidatorInterface $requestValidator,
        GetCarrierTitle $getCarrierTitle,
        array $data = []
    ) {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->getShippingPrice = $getShippingPrice;
        $this->requestValidator = $requestValidator;
        $this->getCarrierTitle = $getCarrierTitle;

        $this->_code = self::CARRIER_CODE;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @inheritdoc
     */
    public function processAdditionalValidation(DataObject $request)
    {
        /** @var RateRequest $request */
        $validationResult = $this->requestValidator->validate($request);

        if (!$validationResult->isValid()) {
            return $this->createErrorResult();
        }

        return $validationResult->isValid();
    }

    /**
     * Build shipping method error message.
     *
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

        $shippingPrice = $this->getShippingPrice->execute($request);

        $result = $this->rateResultFactory->create();

        $method = $this->createResultMethod($shippingPrice);
        $result->append($method);

        return $result;
    }

    /**
     * Create rate object based on shipping price.
     *
     * @param float $shippingPrice
     * @return Method
     */
    private function createResultMethod(float $shippingPrice): Method
    {
        $store = $this->getStore();

        if ($store instanceof StoreInterface) {
            $store = $store->getId();
        }

        $method = $this->rateMethodFactory->create(
            [
                'data' => [
                    'carrier' => self::CARRIER_CODE,
                    'carrier_title' => $this->getCarrierTitle->execute((int)$store),
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
        return [self::METHOD_CODE => $this->getConfigData('name')];
    }
}
