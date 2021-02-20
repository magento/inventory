<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Command;

use Magento\InventoryReservationCli\Model\GetSalableQuantityInconsistencies;
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param GetSalableQuantityInconsistencies $getSalableQuantityInconsistencies
     * @param FilterCompleteOrders $filterCompleteOrders
     * @param FilterIncompleteOrders $filterIncompleteOrders
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetSalableQuantityInconsistencies $getSalableQuantityInconsistencies,
        FilterCompleteOrders $filterCompleteOrders,
        FilterIncompleteOrders $filterIncompleteOrders,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->getSalableQuantityInconsistencies = $getSalableQuantityInconsistencies;
        $this->filterCompleteOrders = $filterCompleteOrders;
        $this->filterIncompleteOrders = $filterIncompleteOrders;
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
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $startTime = microtime(true);
        $isRawOutput = (bool)$input->getOption('raw');
        $bunchSize = (int)$input->getOption('bunch-size');
        $hasInconsistencies = false;
        $startBunchExecution = microtime(true);
        $page = 1;
        foreach ($this->getSalableQuantityInconsistencies->execute($bunchSize) as $inconsistencies) {
            if ($input->getOption('complete-orders')) {
                $inconsistencies = $this->filterCompleteOrders->execute($inconsistencies);
            } elseif ($input->getOption('incomplete-orders')) {
                $inconsistencies = $this->filterIncompleteOrders->execute($inconsistencies);
            }

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
            $page++;
            $hasInconsistencies = $hasInconsistencies || !empty($inconsistencies);
            $startBunchExecution = microtime(true);
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
}
