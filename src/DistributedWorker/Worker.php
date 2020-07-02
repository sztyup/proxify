<?php

declare(strict_types=1);

namespace Proxify\DistributedWorker;

use GuzzleHttp\Client;
use Throwable;

class Worker
{
    protected Client $client;

    protected QueueHandler $queueHandler;

    protected TaskHandler $taskHandler;

    public function __construct()
    {
        $this->client       = new Client();
        $this->queueHandler = new QueueHandler();
        $this->taskHandler  = new TaskHandler();
    }

    public function run(int $count = 10): int
    {
        for ($i = 1; $i <= $count; $i++) {
            $task = $this->queueHandler->getTask();

            if ($task === null) {
                return $i - 1;
            }

            $this->tryTask($task);
        }

        return $i;
    }

    protected function tryTask(Task $task): void
    {
        $code = $this->request($task->getUrl());

        $this->queueHandler->removeTask($task);

        $this->taskHandler->saveTaskResult($task, $code);
    }

    protected function request(string $url): ?int
    {
        try {
            $result = $this->client->get($url);
        } catch (Throwable $throwable) {
            return null;
        }

        return $result->getStatusCode();
    }
}
