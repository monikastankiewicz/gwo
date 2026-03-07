<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Symfony\Component\HttpFoundation\Response;

/**
 * Stores HTTP responses during a Behat scenario.
 */
final class SharedStorage
{
    /**
     * @var Response[]
     */
    private array $responses = [];

    public function addResponse(Response $response): void
    {
        $this->responses[] = $response;
    }

    public function getLastResponse(): ?Response
    {
        if ($this->responses === []) {
            return null;
        }

        return $this->responses[array_key_last($this->responses)];
    }

    public function getResponse(int $index): ?Response
    {
        return $this->responses[$index] ?? null;
    }

    /**
     * @return Response[]
     */
    public function getResponses(): array
    {
        return $this->responses;
    }

    /**
     * @BeforeScenario
     */
    public function clear(): void
    {
        $this->responses = [];
    }
}