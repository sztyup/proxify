<?php

declare(strict_types=1);

namespace Proxify\DistributedWorker;

use PDO;

class TaskHandler
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = new PDO(
            $_ENV['DATABASE_DSN'],
            $_ENV['DATABASE_USERNAME'],
            $_ENV['DATABASE_PASSWORD'],
            [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION
            ]
        );
    }

    public function fetchTasks(int $limit): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tasks WHERE status="NEW" ORDER BY id LIMIT :limit');

        $stmt->bindParam('limit', $limit, PDO::PARAM_INT);

        $stmt->execute();

        $tasks = [];
        $ids   = [];

        foreach ($stmt->fetchAll() as $row) {
            $tasks[] = new Task((int)$row['id'], $row['url']);
            $ids[]   = (int)$row['id'];
        }

        if (empty($tasks)) {
            return [];
        }

        $this->pdo->exec('UPDATE tasks SET status="PROCESSING" WHERE id IN (' . implode(', ', $ids) . ')');

        return $tasks;
    }

    public function saveTaskResult(Task $task, ?int $code): void
    {
        if ($code === null) {
            $stmt = $this->pdo->prepare('UPDATE tasks SET status="ERROR" WHERE id=:id');

            $stmt->execute(
                [
                    'id' => $task->getId()
                ]
            );
        } else {
            $stmt = $this->pdo->prepare('UPDATE tasks SET status="DONE", http_code=:code WHERE id=:id');

            $stmt->execute(
                [
                    'code' => $code,
                    'id'   => $task->getId()
                ]
            );
        }
    }
}
