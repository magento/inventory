<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ProductAlert\Model;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Inventory\Model\IsProductInStock;
use Magento\InventorySales\Model\StockResolver;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\ProductAlert\Model\Email;
use Magento\ProductAlert\Model\EmailFactory;
use Magento\ProductAlert\Model\ResourceModel\Price\CollectionFactory;
use Magento\ProductAlert\Model\ResourceModel\Stock\CollectionFactory as StockCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * ProductAlert observer
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Observer
{
    /**
     * Error email template configuration
     */
    const XML_PATH_ERROR_TEMPLATE = 'catalog/productalert_cron/error_email_template';

    /**
     * Error email identity configuration
     */
    const XML_PATH_ERROR_IDENTITY = 'catalog/productalert_cron/error_email_identity';

    /**
     * 'Send error emails to' configuration
     */
    const XML_PATH_ERROR_RECIPIENT = 'catalog/productalert_cron/error_email';

    /**
     * Allow price alert
     *
     */
    const XML_PATH_PRICE_ALLOW = 'catalog/productalert/allow_price';

    /**
     * Allow stock alert
     *
     */
    const XML_PATH_STOCK_ALLOW = 'catalog/productalert/allow_stock';

    /**
     * Website collection array
     *
     * @var array
     */
    private $websites;

    /**
     * Warning (exception) errors array
     *
     * @var array
     */
    private $errors = [];

    /**
     * Catalog data
     *
     * @var \Magento\Catalog\Helper\Data
     */
    private $catalogData = null;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CollectionFactory
     */
    private $priceColFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory
     */
    private $dateFactory;

    /**
     * @var \Magento\ProductAlert\Model\ResourceModel\Stock\CollectionFactory
     */
    private $stockColFactory;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var \Magento\ProductAlert\Model\EmailFactory
     */
    private $emailFactory;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    private $inlineTranslation;

    /**
     * @var \Magento\Inventory\Model\IsProductInStock
     */
    private $stockItem;

    /**
     * @var \Magento\InventorySales\Model\StockResolver
     */
    private $stockResolver;


    /**
     * @param \Magento\Catalog\Helper\Data                                      $catalogData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface                $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface                        $storeManager
     * @param \Magento\ProductAlert\Model\ResourceModel\Price\CollectionFactory $priceColFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface                 $customerRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface                   $productRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTimeFactory                $dateFactory
     * @param \Magento\ProductAlert\Model\ResourceModel\Stock\CollectionFactory $stockColFactory
     * @param \Magento\Framework\Mail\Template\TransportBuilder                 $transportBuilder
     * @param \Magento\ProductAlert\Model\EmailFactory                          $emailFactory
     * @param \Magento\Framework\Translate\Inline\StateInterface                $inlineTranslation
     * @param \Magento\Inventory\Model\IsProductInStock                         $stockItem
     * @param \Magento\InventorySales\Model\StockResolver                       $stockResolver
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Data $catalogData,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CollectionFactory $priceColFactory,
        CustomerRepositoryInterface $customerRepository,
        ProductRepositoryInterface $productRepository,
        DateTimeFactory $dateFactory,
        StockCollectionFactory $stockColFactory,
        TransportBuilder $transportBuilder,
        EmailFactory $emailFactory,
        StateInterface $inlineTranslation,
        IsProductInStock $stockItem,
        StockResolver $stockResolver
    ) {
        $this->catalogData        = $catalogData;
        $this->scopeConfig        = $scopeConfig;
        $this->storeManager       = $storeManager;
        $this->priceColFactory    = $priceColFactory;
        $this->customerRepository = $customerRepository;
        $this->productRepository  = $productRepository;
        $this->dateFactory        = $dateFactory;
        $this->stockColFactory    = $stockColFactory;
        $this->transportBuilder   = $transportBuilder;
        $this->emailFactory       = $emailFactory;
        $this->inlineTranslation  = $inlineTranslation;
        $this->stockItem          = $stockItem ?: ObjectManager::getInstance()->get(IsProductInStock::class);
        $this->stockResolver      = $stockResolver ?: ObjectManager::getInstance()->get(StockResolver::class);
    }

    /**
     * Retrieve website collection array
     *
     * @return array
     * @throws \Exception
     */
    protected function _getWebsites()
    {
        if ($this->websites === null) {
            try {
                $this->websites = $this->storeManager->getWebsites();
            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
                throw $e;
            }
        }

        return $this->websites;
    }

    /**
     * Process price emails
     *
     * @param \Magento\ProductAlert\Model\Email $email
     *
     * @return $this
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _processPrice(Email $email): Observer
    {
        $email->setType('price');
        foreach ($this->_getWebsites() as $website) {
            /* @var $website \Magento\Store\Model\Website */
            if (!$website->getDefaultGroup() || !$website->getDefaultGroup()->getDefaultStore()) {
                continue;
            }
            if (!$this->scopeConfig->getValue(
                self::XML_PATH_PRICE_ALLOW,
                ScopeInterface::SCOPE_STORE,
                $website->getDefaultGroup()->getDefaultStore()->getId()
            )
            ) {
                continue;
            }
            try {
                $collection = $this->priceColFactory->create()->addWebsiteFilter(
                    $website->getId()
                )->setCustomerOrder();
            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
                throw $e;
            }

            $previousCustomer = null;
            $email->setWebsite($website);
            foreach ($collection as $alert) {
                try {
                    if (!$previousCustomer || $previousCustomer->getId() != $alert->getCustomerId()) {
                        $customer = $this->customerRepository->getById($alert->getCustomerId());
                        if ($previousCustomer) {
                            $email->send();
                        }
                        if (!$customer) {
                            continue;
                        }
                        $previousCustomer = $customer;
                        $email->clean();
                        $email->setCustomerData($customer);
                    } else {
                        $customer = $previousCustomer;
                    }

                    $product = $this->productRepository->getById(
                        $alert->getProductId(),
                        false,
                        $website->getDefaultStore()->getId()
                    );

                    $product->setCustomerGroupId($customer->getGroupId());
                    if ($alert->getPrice() > $product->getFinalPrice()) {
                        $productPrice = $product->getFinalPrice();
                        $product->setFinalPrice($this->catalogData->getTaxPrice($product, $productPrice));
                        $product->setPrice($this->catalogData->getTaxPrice($product, $product->getPrice()));
                        $email->addPriceProduct($product);

                        $alert->setPrice($productPrice);
                        $alert->setLastSendDate($this->dateFactory->create()->gmtDate());
                        $alert->setSendCount($alert->getSendCount() + 1);
                        $alert->setStatus(1);
                        $alert->save();
                    }
                } catch (\Exception $e) {
                    $this->errors[] = $e->getMessage();
                    throw $e;
                }
            }
            if ($previousCustomer) {
                try {
                    $email->send();
                } catch (\Exception $e) {
                    $this->errors[] = $e->getMessage();
                    throw $e;
                }
            }
        }

        return $this;
    }

    /**
     * Process stock emails
     *
     * @param \Magento\ProductAlert\Model\Email $email
     *
     * @return $this
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _processStock(Email $email): Observer
    {
        $email->setType('stock');

        foreach ($this->_getWebsites() as $website) {
            /* @var $website \Magento\Store\Model\Website */

            if (!$website->getDefaultGroup() || !$website->getDefaultGroup()->getDefaultStore()) {
                continue;
            }
            if (!$this->scopeConfig->getValue(
                self::XML_PATH_STOCK_ALLOW,
                ScopeInterface::SCOPE_STORE,
                $website->getDefaultGroup()->getDefaultStore()->getId()
            )
            ) {
                continue;
            }
            try {
                $collection = $this->stockColFactory->create()->addWebsiteFilter(
                    $website->getId()
                )->addStatusFilter(
                    0
                )->setCustomerOrder();
            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
                throw $e;
            }

            $previousCustomer = null;
            $email->setWebsite($website);
            foreach ($collection as $alert) {
                try {
                    if (!$previousCustomer || $previousCustomer->getId() != $alert->getCustomerId()) {
                        $customer = $this->customerRepository->getById($alert->getCustomerId());
                        if ($previousCustomer) {
                            $email->send();
                        }
                        if (!$customer) {
                            continue;
                        }
                        $previousCustomer = $customer;
                        $email->clean();
                        $email->setCustomerData($customer);
                    } else {
                        $customer = $previousCustomer;
                    }

                    $product = $this->productRepository->getById(
                        $alert->getProductId(),
                        false,
                        $website->getDefaultStore()->getId()
                    );

                    $product->setCustomerGroupId($customer->getGroupId());
                    $stock = $this->stockResolver->get(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());

                    if ($this->stockItem->execute($product->getSku(), $stock->getStockId())) {
                        $email->addStockProduct($product);

                        $alert->setSendDate($this->dateFactory->create()->gmtDate());
                        $alert->setSendCount($alert->getSendCount() + 1);
                        $alert->setStatus(1);
                        $alert->save();
                    }
                } catch (\Exception $e) {
                    $this->errors[] = $e->getMessage();
                    throw $e;
                }
            }

            if ($previousCustomer) {
                try {
                    $email->send();
                } catch (\Exception $e) {
                    $this->errors[] = $e->getMessage();
                    throw $e;
                }
            }
        }

        return $this;
    }

    /**
     * Send email to administrator if error
     *
     * @return $this
     * @throws \Magento\Framework\Exception\MailException
     */
    protected function _sendErrorEmail(): Observer
    {
        if (count($this->errors)) {
            if (!$this->scopeConfig->getValue(
                self::XML_PATH_ERROR_TEMPLATE,
                ScopeInterface::SCOPE_STORE
            )
            ) {
                return $this;
            }

            $this->inlineTranslation->suspend();

            $transport = $this->transportBuilder->setTemplateIdentifier(
                $this->scopeConfig->getValue(
                    self::XML_PATH_ERROR_TEMPLATE,
                    ScopeInterface::SCOPE_STORE
                )
            )->setTemplateOptions(
                [
                    'area'  => FrontNameResolver::AREA_CODE,
                    'store' => Store::DEFAULT_STORE_ID,
                ]
            )->setTemplateVars(
                ['warnings' => join("\n", $this->errors)]
            )->setFrom(
                $this->scopeConfig->getValue(
                    self::XML_PATH_ERROR_IDENTITY,
                    ScopeInterface::SCOPE_STORE
                )
            )->addTo(
                $this->scopeConfig->getValue(
                    self::XML_PATH_ERROR_RECIPIENT,
                    ScopeInterface::SCOPE_STORE
                )
            )->getTransport();

            $transport->sendMessage();

            $this->inlineTranslation->resume();
            $this->errors[] = [];
        }

        return $this;
    }

    /**
     * Run process send product alerts
     *
     * @return $this
     * @throws \Exception
     * @throws \Magento\Framework\Exception\MailException
     */
    public function process()
    {
        /* @var $email Email */
        $email = $this->emailFactory->create();
        $this->_processPrice($email);
        $this->_processStock($email);
        $this->_sendErrorEmail();

        return $this;
    }
}
