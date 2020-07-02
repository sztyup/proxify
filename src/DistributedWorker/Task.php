<?php

declare(strict_types=1);

namespace Proxify\DistributedWorker;

use JsonSerializable;

class Task implements JsonSerializable
{
    private int $id;

    private string $url;

    private ?int $queueId;

    public function __construct(int $id, string $url, ?int $queueId = null)
    {
        $this->id      = $id;
        $this->url     = $url;
        $this->queueId = $queueId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getQueueId(): ?int
    {
        return $this->queueId;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'  => $this->getId(),
            'url' => $this->getUrl(),
        ];
    }
}
