<?php

declare(strict_types=1);

namespace Proxify\DistributedWorker;

use Pheanstalk\JobId;
use Pheanstalk\Pheanstalk;
use Throwable;

class QueueHandler
{
    private Pheanstalk $beanstalkd;

    public function __construct()
    {
        $this->beanstalkd = Pheanstalk::create(
            $_ENV['BEANSTALKD_HOSTNAME'],
            (int)$_ENV['BEANSTALKD_PORT'],
        );
    }

    public function addTask(Task $task): void
    {
        $this->beanstalkd->put(json_encode($task, JSON_THROW_ON_ERROR));
    }

    public function getTask(): ?Task
    {
        try {
            $job = $this->beanstalkd->reserveWithTimeout(0);
        } catch (Throwable $throwable) {
            return null;
        }

        if ($job === null) {
            return null;
        }

        try {
            $data = json_decode($job->getData(), true, 2, JSON_THROW_ON_ERROR);
        } catch (Throwable $throwable) {
            return null;
        }

        return new Task($data['id'], $data['url'], $job->getId());
    }

    public function removeTask(Task $task): void
    {
        $this->beanstalkd->delete(
            new JobId($task->getQueueId())
        );
    }
}
