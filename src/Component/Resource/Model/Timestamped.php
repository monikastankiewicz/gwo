<?php

namespace App\Component\Resource\Model;

use Symfony\Component\Serializer\Annotation\Ignore;

interface Timestamped
{
    public function getUpdatedAt(): ?\DateTimeImmutable;

    public function getCreatedAt(): ?\DateTimeImmutable;

    public function markUpdated(): void;

    public function markCreated(): void;

    #[Ignore]
    public function isNew(): bool;
}
