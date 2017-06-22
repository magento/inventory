<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Quote\Model\Quote;

class PackageCalculator
{
    /**
     * @var Address\RateRequestFactory
     */
    private $rateRequestFactory;

    /**
     * @var \Magento\Inventory\Model\SourceSelectionInterface
     */
    private $sourceSelection;

    /**
     * @var Address\RateCollectorInterfaceFactory
     */
    private $rateCollector;

    /**
     * @param Address\RateRequestFactory $rateRequestFactory
     * @param \Magento\Inventory\Model\SourceSelectionInterface $sourceSelection
     * @param Address\RateCollectorInterfaceFactory $rateCollector
     */
    public function __construct(
        \Magento\Quote\Model\Quote\Address\RateRequestFactory $rateRequestFactory,
        \Magento\Inventory\Model\SourceSelectionInterface $sourceSelection,
        \Magento\Quote\Model\Quote\Address\RateCollectorInterfaceFactory $rateCollector
    ) {
        $this->rateRequestFactory = $rateRequestFactory;
        $this->sourceSelection = $sourceSelection;
        $this->rateCollector = $rateCollector;
    }

    /**
     * Calculate the quantity of items and the weight of the package
     *
     * @param \Magento\Inventory\Model\Package $package
     * @param \Magento\Quote\Model\Quote\Address $destinationAddress
     * @return array
     */
    public function calculatePackageMetrics($package, $destinationAddress)
    {
        $result = [];
        foreach ($package->getItems() as $item) {
            /**
             * Skip if this item is virtual
             */
            if ($item->getProduct()->isVirtual()) {
                continue;
            }

            /**
             * Children weight we calculate for parent
             */
            if ($item->getParentItem()) {
                continue;
            }

            if ($item->getHasChildren() && $item->isShipSeparately()) {
                foreach ($item->getChildren() as $child) {
                    if ($child->getProduct()->isVirtual()) {
                        continue;
                    }
                    $result['package_qty'] += $child->getTotalQty();

                    if (!$item->getProduct()->getWeightType()) {
                        $itemWeight = $child->getWeight();
                        $itemQty = $child->getTotalQty();
                        $rowWeight = $itemWeight * $itemQty;
                        $result['package_weight'] += $rowWeight;
                        if ($destinationAddress->getFreeShipping() || $child->getFreeShipping() === true) {
                            $rowWeight = 0;
                        } elseif (is_numeric($child->getFreeShipping())) {
                            $freeQty = $child->getFreeShipping();
                            if ($itemQty > $freeQty) {
                                $rowWeight = $itemWeight * ($itemQty - $freeQty);
                            } else {
                                $rowWeight = 0;
                            }
                        }
                        $result['package_freemethod_weight'] += $rowWeight;
                        $item->setRowWeight($rowWeight);
                    }
                }
                if ($item->getProduct()->getWeightType()) {
                    $itemWeight = $item->getWeight();
                    $rowWeight = $itemWeight * $item->getQty();
                    $result['package_weight'] += $rowWeight;
                    if ($destinationAddress->getFreeShipping() || $item->getFreeShipping() === true) {
                        $rowWeight = 0;
                    } elseif (is_numeric($item->getFreeShipping())) {
                        $freeQty = $item->getFreeShipping();
                        if ($item->getQty() > $freeQty) {
                            $rowWeight = $itemWeight * ($item->getQty() - $freeQty);
                        } else {
                            $rowWeight = 0;
                        }
                    }
                    $result['package_freemethod_weight'] += $rowWeight;
                    $item->setRowWeight($rowWeight);
                }
            } else {
                if (!$item->getProduct()->isVirtual()) {
                    $result['package_qty'] += $item->getQty();
                }
                $itemWeight = $item->getWeight();
                $rowWeight = $itemWeight * $item->getQty();
                $result['package_weight'] += $rowWeight;
                if ($destinationAddress->getFreeShipping() || $item->getFreeShipping() === true) {
                    $rowWeight = 0;
                } elseif (is_numeric($item->getFreeShipping())) {
                    $freeQty = $item->getFreeShipping();
                    if ($item->getQty() > $freeQty) {
                        $rowWeight = $itemWeight * ($item->getQty() - $freeQty);
                    } else {
                        $rowWeight = 0;
                    }
                }
                $result['package_freemethod_weight'] += $rowWeight;
                $item->setRowWeight($rowWeight);
            }
        }
        return $result;
    }

    public function collectShippingRates($package, $destinationAddress)
    {
        /** @var $request \Magento\Quote\Model\Quote\Address\RateRequest */
        $request = $this->rateRequestFactory->create();
        $request->setAllItems($package->getItems());

        // disable auto-setting of origin in \Magento\Shipping\Model\Shipping::collectRates
        $request->setOrig(true);

        // read origin address from the Source assigned to the Package
        $request->setCountryId($package->getSource()->getCountryId());
        $request->setRegionId($package->getSource()->getRegionId());
        $request->setCity($package->getSource()->getCity());
        $request->setPostcode($package->getSource()->getPostcode());

        $request->setDestCountryId($destinationAddress->getCountryId());
        $request->setDestRegionId($destinationAddress->getRegionId());
        $request->setDestRegionCode($destinationAddress->getRegionCode());
        $request->setDestStreet($destinationAddress->getStreetFull());
        $request->setDestCity($destinationAddress->getCity());
        $request->setDestPostcode($destinationAddress->getPostcode());

        //$item ? $item->getBaseRowTotal() : $this->getBaseSubtotal()
        $request->setPackageValue($package->getBaseSubtotal());
        //$packageWithDiscount = $item ? $item->getBaseRowTotal() -
        //$item->getBaseDiscountAmount() : $this->getBaseSubtotalWithDiscount();
        $packageWithDiscount = $package->getBaseSubtotalWithDiscount();
        $request->setPackageValueWithDiscount($packageWithDiscount);
        //$item ? $item->getRowWeight() : $this->getWeight()
        $request->setPackageWeight($package->getWeight());
        //$item ? $item->getQty() : $this->getItemQty()
        $request->setPackageQty($package->getItemQty());

        /**
         * Need for shipping methods that use insurance based on price of physical products
         */
//            $packagePhysicalValue = $item ? $item->getBaseRowTotal() : $this->getBaseSubtotal() -
//                $this->getBaseVirtualAmount();
//            $request->setPackagePhysicalValue($packagePhysicalValue);
        $request->setPackagePhysicalValue($package->getPhysicalValue());

        $request->setFreeMethodWeight($destinationAddress->getFreeMethodWeight());

        /**
         * Store and website identifiers need specify from quote
         */
        $request->setStoreId($destinationAddress->getQuote()->getStore()->getId());
        $request->setWebsiteId($destinationAddress->getQuote()->getStore()->getWebsiteId());
        $request->setFreeShipping($destinationAddress->getFreeShipping());
        /**
         * Currencies need to convert in free shipping
         */
        $request->setBaseCurrency($destinationAddress->getQuote()->getStore()->getBaseCurrency());
        $request->setPackageCurrency($destinationAddress->getQuote()->getStore()->getCurrentCurrency());

        //get carriers from source
        $request->setLimitCarrier($destinationAddress->getLimitCarrier());
        $baseSubtotalInclTax = $destinationAddress->getBaseSubtotalTotalInclTax();
        $request->setBaseSubtotalInclTax($baseSubtotalInclTax);
        $result = $this->rateCollector->create()->collectRates($request)->getResult();
    }
}