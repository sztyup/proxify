<?php

declare(strict_types=1);

namespace Proxify\DistributedWorker;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DistributorCommand extends Command
{
    use LockableTrait;

    protected static string $defaultName = 'run-distributor';

    protected bool $running = true;

    protected function configure(): void
    {
        $this
            ->setDescription('Runs the distributor')
            ->addArgument('batch', InputArgument::OPTIONAL, 'The number of tasks processed in one run', 10)
            ->addArgument('sleep', InputArgument::OPTIONAL, 'The number of seconds to wait between runs', 2)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('A distributor is already running, it cannot be run in parallel');

            return 1;
        }

        $output->writeln('Booting distributor');

        $this->handleSignals();

        $worker = new Distributor();
        $batch  = (int)$input->getArgument('batch');

        while ($this->running) {
            $tasks = $worker->run($batch);

            $output->writeln("Run completed, $tasks tasks enqueued");

            sleep((int)$input->getArgument('sleep'));

            pcntl_signal_dispatch();
        }

        $output->writeln("\nExiting...");

        $this->release();

        return 0;
    }

    private function handleSignals(): void
    {
        $handler = function () {
            $this->running = false;
        };

        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
        pcntl_signal(SIGHUP, $handler);
    }
}
