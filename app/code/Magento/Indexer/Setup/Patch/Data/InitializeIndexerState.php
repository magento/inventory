<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Indexer\Setup\Patch\Data;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Indexer\ConfigInterface;
use Magento\Indexer\Model\ResourceModel\Indexer\State\CollectionFactory;
use Magento\Indexer\Model\Indexer\StateFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Class InitializeIndexerState
 * @package Magento\Indexer\Setup\Patch
 */
class InitializeIndexerState implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CollectionFactory
     */
    private $statesFactory;

    /**
     * @var StateFactory
     */
    private $stateFactory;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * PatchInitial constructor.
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param CollectionFactory $statesFactory
     * @param StateFactory $stateFactory
     * @param ConfigInterface $config
     * @param EncryptorInterface $encryptor
     * @param EncoderInterface $encoder
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        CollectionFactory $statesFactory,
        StateFactory $stateFactory,
        ConfigInterface $config,
        EncryptorInterface $encryptor,
        EncoderInterface $encoder,
        IndexerRegistry $indexerRegistry = null
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->statesFactory = $statesFactory;
        $this->stateFactory = $stateFactory;
        $this->config = $config;
        $this->encryptor = $encryptor;
        $this->encoder = $encoder;
        $this->indexerRegistry = $indexerRegistry ? : ObjectManager::getInstance()->get(IndexerRegistry::class);
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        foreach ($this->config->getIndexers() as $indexerId => $indexerConfig) {
            $hash = $this->encryptor->hash($this->encoder->encode($indexerConfig), Encryptor::HASH_VERSION_MD5);
            $indexerState = $this->indexerRegistry->get($indexerId)->getState();
            $indexerState->setHashConfig($hash);
            $indexerState->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.1.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
