<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventoryReservationCli\Model\GetSalableQuantityInconsistencies;
use Magento\InventoryReservationCli\Model\ResourceModel\GetOrdersTotalCount;
use Magento\InventoryReservationCli\Model\SalableQuantityInconsistency;
use Magento\InventoryReservationCli\Model\SalableQuantityInconsistency\FilterCompleteOrders;
use Magento\InventoryReservationCli\Model\SalableQuantityInconsistency\FilterIncompleteOrders;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Outputs a list of uncompensated reservations linked to the orders
 *
 * This command may be used to simplify migrations from Magento versions without new Inventory or to track down
 * incorrect behavior of customizations.
 */
class ShowInconsistencies extends Command
{
    /**
     * @var GetSalableQuantityInconsistencies
     */
    private $getSalableQuantityInconsistencies;

    /**
     * @var FilterCompleteOrders
     */
    private $filterCompleteOrders;

    /**
     * @var FilterIncompleteOrders
     */
    private $filterIncompleteOrders;

    /**
     * @var GetOrdersTotalCount
     */
    private $getOrdersTotalCount;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param GetSalableQuantityInconsistencies $getSalableQuantityInconsistencies
     * @param FilterCompleteOrders $filterCompleteOrders
     * @param FilterIncompleteOrders $filterIncompleteOrders
     * @param GetOrdersTotalCount $getOrdersTotalCount
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetSalableQuantityInconsistencies $getSalableQuantityInconsistencies,
        FilterCompleteOrders $filterCompleteOrders,
        FilterIncompleteOrders $filterIncompleteOrders,
        GetOrdersTotalCount $getOrdersTotalCount,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->getSalableQuantityInconsistencies = $getSalableQuantityInconsistencies;
        $this->filterCompleteOrders = $filterCompleteOrders;
        $this->filterIncompleteOrders = $filterIncompleteOrders;
        $this->getOrdersTotalCount = $getOrdersTotalCount;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('inventory:reservation:list-inconsistencies')
            ->setDescription('Show all orders and products with salable quantity inconsistencies')
            ->addOption(
                'complete-orders',
                'c',
                InputOption::VALUE_NONE,
                'Show only inconsistencies for complete orders'
            )
            ->addOption(
                'incomplete-orders',
                'i',
                InputOption::VALUE_NONE,
                'Show only inconsistencies for incomplete orders'
            )
            ->addOption(
                'bunch-size',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Defines how many orders will be loaded at once',
                50
            )
            ->addOption(
                'raw',
                'r',
                InputOption::VALUE_NONE,
                'Raw output'
            );

        parent::configure();
    }

    /**
     * Format output
     *
     * @param OutputInterface $output
     * @param SalableQuantityInconsistency[] $inconsistencies
     */
    private function prettyOutput(OutputInterface $output, array $inconsistencies): void
    {
        /** @var Order $order */
        foreach ($inconsistencies as $inconsistency) {
            $inconsistentItems = $inconsistency->getItems();

            $output->writeln(
                sprintf(
                    'Order <comment>%s</comment>:',
                    $inconsistency->getOrderIncrementId()
                )
            );

            foreach ($inconsistentItems as $sku => $qty) {
                $output->writeln(
                    sprintf(
                        '  - Product <comment>%s</comment> should be compensated by '
                        . '<comment>%+f</comment> for stock <comment>%s</comment>',
                        $sku,
                        -$qty,
                        $inconsistency->getStockId()
                    )
                );
            }
        }
    }

    /**
     * Output without formatting
     *
     * @param OutputInterface $output
     * @param SalableQuantityInconsistency[] $inconsistencies
     */
    private function rawOutput(OutputInterface $output, array $inconsistencies): void
    {
        /** @var Order $order */
        foreach ($inconsistencies as $inconsistency) {
            $inconsistentItems = $inconsistency->getItems();

            foreach ($inconsistentItems as $sku => $qty) {
                $output->writeln(
                    sprintf(
                        '%s:%s:%f:%s',
                        $inconsistency->getOrderIncrementId(),
                        $sku,
                        -$qty,
                        $inconsistency->getStockId()
                    )
                );
            }
        }
    }

    /**
     * @inheritdoc
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws LocalizedException
     * @throws ValidationException
     * @throws SkuIsNotAssignedToStockException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $startTime = microtime(true);
        $isRawOutput = (bool)$input->getOption('raw');
        $bunchSize = (int)$input->getOption('bunch-size');

        $maxPage = $this->retrieveMaxPage($bunchSize);
        $hasInconsistencies = false;

        for ($page = 1; $page <= $maxPage; $page++) {
            $startBunchExecution = microtime(true);

            $inconsistencies = $this->getSalableQuantityInconsistencies->execute($bunchSize, $page);
            if ($input->getOption('complete-orders')) {
                $inconsistencies = $this->filterCompleteOrders->execute($inconsistencies);
            } elseif ($input->getOption('incomplete-orders')) {
                $inconsistencies = $this->filterIncompleteOrders->execute($inconsistencies);
            }

            $hasInconsistencies = !empty($inconsistencies);

            if ($isRawOutput) {
                $this->rawOutput($output, $inconsistencies);
            } else {
                $this->prettyOutput($output, $inconsistencies);
            }

            $this->logger->debug(
                'Bunch processed for reservation inconsistency check',
                [
                    'duration' => sprintf('%.2fs', (microtime(true) - $startBunchExecution)),
                    'memory_usage' => sprintf('%.2fMB', (memory_get_peak_usage(true) / 1024 / 1024)),
                    'bunch_size' => $bunchSize,
                    'page' => $page,
                ]
            );
        }

        if ($hasInconsistencies === false) {
            $output->writeln('<info>No order inconsistencies were found</info>');
            return 0;
        }

        $this->logger->debug(
            'Finished reservation inconsistency check',
            [
                'duration' => sprintf('%.2fs', (microtime(true) - $startTime)),
                'memory_usage' => sprintf('%.2fMB', (memory_get_peak_usage(true) / 1024 / 1024)),
            ]
        );

        return -1;
    }

    /**
     * Retrieve max page for given bunch size
     *
     * @param int $bunchSize
     * @return int
     */
    private function retrieveMaxPage(int $bunchSize): int
    {
        $ordersTotalCount = $this->getOrdersTotalCount->execute();
        return (int)ceil($ordersTotalCount / $bunchSize);
    }
}
