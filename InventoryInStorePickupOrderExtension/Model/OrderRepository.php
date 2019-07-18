<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupOrderExtension\Model;

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Payment\Api\Data\PaymentAdditionalInfoInterface;
use Magento\Payment\Api\Data\PaymentAdditionalInfoInterfaceFactory;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterfaceFactory;
use Magento\Sales\Api\Data\ShippingAssignmentInterface;
use Magento\Sales\Model\Order\ShippingAssignmentBuilder;
use Magento\Sales\Model\ResourceModel\Metadata;
use Magento\Tax\Api\OrderTaxManagementInterface;

/**
 * Replace for Order Repository in case to add Extension Attributes Join.
 *
 * Class is available to fix incompatibility with Magneto 2.3.1.
 *
 * @see Please check original issue in core repository for more details https://github.com/magento/magento2/pull/21797.
 * @deprecated The class will be removed when support of InStorePickup module will end for Magneto 2.3.1.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderRepository extends \Magento\Sales\Model\OrderRepository
{
    /**
     * @var OrderExtensionFactory
     */
    private $orderExtensionFactory;

    /**
     * @var ShippingAssignmentBuilder
     */
    private $shippingAssignmentBuilder;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var OrderTaxManagementInterface
     */
    private $orderTaxManagement;

    /**
     * @var PaymentAdditionalInfoFactory
     */
    private $paymentAdditionalInfoFactory;

    /**
     * @var JsonSerializer
     */
    private $serializer;

    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @param Metadata $metadata
     * @param OrderSearchResultInterfaceFactory $searchResultFactory
     * @param CollectionProcessorInterface|null $collectionProcessor
     * @param OrderExtensionFactory|null $orderExtensionFactory
     * @param OrderTaxManagementInterface|null $orderTaxManagement
     * @param PaymentAdditionalInfoInterfaceFactory|null $paymentAdditionalInfoFactory
     * @param JsonSerializer|null $serializer
     * @param ShippingAssignmentBuilder|null $shippingAssignmentBuilder
     * @param JoinProcessorInterface|null $extensionAttributesJoinProcessor
     */
    public function __construct(
        Metadata $metadata,
        OrderSearchResultInterfaceFactory $searchResultFactory,
        CollectionProcessorInterface $collectionProcessor = null,
        OrderExtensionFactory $orderExtensionFactory = null,
        OrderTaxManagementInterface $orderTaxManagement = null,
        PaymentAdditionalInfoInterfaceFactory $paymentAdditionalInfoFactory = null,
        JsonSerializer $serializer = null,
        ShippingAssignmentBuilder $shippingAssignmentBuilder = null,
        JoinProcessorInterface $extensionAttributesJoinProcessor = null
    ) {
        parent::__construct(
            $metadata,
            $searchResultFactory,
            $collectionProcessor,
            $orderExtensionFactory,
            $orderTaxManagement,
            $paymentAdditionalInfoFactory,
            $serializer
        );

        $this->collectionProcessor = $collectionProcessor ?:
            ObjectManager::getInstance()->get(CollectionProcessorInterface::class);
        $this->orderExtensionFactory = $orderExtensionFactory ?:
            ObjectManager::getInstance()->get(OrderExtensionFactory::class);
        $this->orderTaxManagement = $orderTaxManagement ?:
            ObjectManager::getInstance()->get(OrderTaxManagementInterface::class);
        $this->paymentAdditionalInfoFactory = $paymentAdditionalInfoFactory ?:
            ObjectManager::getInstance()->get(PaymentAdditionalInfoInterfaceFactory::class);
        $this->serializer = $serializer ?:
            ObjectManager::getInstance()->get(JsonSerializer::class);
        $this->shippingAssignmentBuilder = $shippingAssignmentBuilder ?:
            ObjectManager::getInstance()->get(ShippingAssignmentBuilder::class);
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor ?:
            ObjectManager::getInstance()->get(JoinProcessorInterface::class);
    }

    /**
     * Set order tax details to extension attributes.
     *
     * @param OrderInterface $order
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function setOrderTaxDetails(OrderInterface $order)
    {
        $extensionAttributes = $order->getExtensionAttributes();
        $orderTaxDetails = $this->orderTaxManagement->getOrderTaxDetails($order->getEntityId());
        $appliedTaxes = $orderTaxDetails->getAppliedTaxes();

        $extensionAttributes->setAppliedTaxes($appliedTaxes);
        if (!empty($appliedTaxes)) {
            $extensionAttributes->setConvertingFromQuote(true);
        }

        $items = $orderTaxDetails->getItems();
        $extensionAttributes->setItemAppliedTaxes($items);

        $order->setExtensionAttributes($extensionAttributes);
    }

    /**
     * Set additional info to the order.
     *
     * @param OrderInterface $order
     */
    private function setPaymentAdditionalInfo(OrderInterface $order): void
    {
        $extensionAttributes = $order->getExtensionAttributes();
        $paymentAdditionalInformation = $order->getPayment()->getAdditionalInformation();

        $objects = [];
        foreach ($paymentAdditionalInformation as $key => $value) {
            /** @var PaymentAdditionalInfoInterface $additionalInformationObject */
            $additionalInformationObject = $this->paymentAdditionalInfoFactory->create();
            $additionalInformationObject->setKey($key);

            if (!is_string($value)) {
                $value = $this->serializer->serialize($value);
            }
            $additionalInformationObject->setValue($value);

            $objects[] = $additionalInformationObject;
        }
        $extensionAttributes->setPaymentAdditionalInfo($objects);
        $order->setExtensionAttributes($extensionAttributes);
    }

    /**
     * @inheritdoc
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        /** @var \Magento\Sales\Api\Data\OrderSearchResultInterface $searchResult */
        $searchResult = $this->searchResultFactory->create();
        $this->extensionAttributesJoinProcessor->process($searchResult);
        $this->collectionProcessor->process($searchCriteria, $searchResult);
        $searchResult->setSearchCriteria($searchCriteria);
        foreach ($searchResult->getItems() as $order) {
            $this->setShippingAssignments($order);
            $this->setOrderTaxDetails($order);
            $this->setPaymentAdditionalInfo($order);
        }
        return $searchResult;
    }

    /**
     * Set shipping assignments to extension attributes.
     *
     * @param OrderInterface $order
     */
    private function setShippingAssignments(OrderInterface $order)
    {
        /** @var OrderExtensionInterface $extensionAttributes */
        $extensionAttributes = $order->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->orderExtensionFactory->create();
        } elseif ($extensionAttributes->getShippingAssignments() !== null) {
            return;
        }
        /** @var ShippingAssignmentInterface $shippingAssignment */
        $shippingAssignments = $this->shippingAssignmentBuilder;
        $shippingAssignments->setOrderId($order->getEntityId());
        $extensionAttributes->setShippingAssignments($shippingAssignments->create());
        $order->setExtensionAttributes($extensionAttributes);
    }
}
