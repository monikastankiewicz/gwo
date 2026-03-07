<?php

declare(strict_types=1);

namespace App\Component\Resource\Model;

trait TimestampedTrait
{
    protected ?\DateTimeImmutable $createdAt = null;
    protected ?\DateTimeImmutable $updatedAt = null;

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function markCreated(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function markUpdated(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isNew(): bool
    {
        return $this->createdAt === null;
    }
}