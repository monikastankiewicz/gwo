<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;

final class ApiContext implements Context
{
    public function __construct(
        private readonly SharedStorage $sharedStorage,
    ) {
    }

    /**
     * @Then /^the response should be received with status code (\d+)$/
     */
    public function theResponseShouldBeReceivedWithStatusCode(int $statusCode): void
    {
        $response = $this->sharedStorage->getLastResponse();

        if ($response === null) {
            throw new \RuntimeException('No response received');
        }

        if ($response->getStatusCode() !== $statusCode) {
            throw new \RuntimeException(sprintf(
                'Expected status code %d, got %d',
                $statusCode,
                $response->getStatusCode()
            ));
        }
    }

    /**
     * @Then the response should equal json:
     */
    public function theResponseShouldEqualJson(PyStringNode $json): void
    {
        $response = $this->sharedStorage->getLastResponse();

        if ($response === null) {
            throw new \RuntimeException('No response received');
        }

        $expected = json_decode($json->getRaw(), true);
        $actual = json_decode($response->getContent(), true);

        unset($expected['id'], $actual['id']);

        if (isset($expected['items']) && isset($actual['items'])) {
            foreach ($expected['items'] as $index => $_item) {
                unset($expected['items'][$index]['id']);
                unset($expected['items'][$index]['product']['id']);
            }

            foreach ($actual['items'] as $index => $_item) {
                unset($actual['items'][$index]['id']);
                unset($actual['items'][$index]['product']['id']);
            }
        }

        if ($expected != $actual) {
            throw new \RuntimeException(sprintf(
                "JSON does not match.\nExpected:\n%s\n\nActual:\n%s",
                json_encode($expected, JSON_PRETTY_PRINT),
                json_encode($actual, JSON_PRETTY_PRINT)
            ));
        }
    }
}
