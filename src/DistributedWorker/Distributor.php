<?php

declare(strict_types=1);

namespace Proxify\DistributedWorker;

class Distributor
{
    private TaskHandler $taskHandler;

    private QueueHandler $queueHandler;

    public function __construct()
    {
        $this->taskHandler  = new TaskHandler();
        $this->queueHandler = new QueueHandler();
    }

    public function run(int $limit = 10): int
    {
        $tasks = $this->taskHandler->fetchTasks($limit);

        $i = 0;
        foreach ($tasks as $task) {
            $this->queueHandler->addTask($task);
            $i++;
        }

        return $i;
    }
}
