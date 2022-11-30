<?php

declare(strict_types=1);

namespace Saloon\Traits;

use Saloon\Contracts\MockClient;

trait HasMockClient
{
    /**
     * Mock Client
     *
     * @var MockClient|null
     */
    protected ?MockClient $mockClient = null;

    /**
     * Specify a mock client.
     *
     * @param MockClient $mockClient
     * @return $this
     */
    public function withMockClient(MockClient $mockClient): static
    {
        $this->mockClient = $mockClient;

        return $this;
    }

    /**
     * Get the mock client.
     *
     * @return MockClient|null
     */
    public function getMockClient(): ?MockClient
    {
        return $this->mockClient;
    }

    /**
     * Determine if the instance has a mock client
     *
     * @return bool
     */
    public function hasMockClient(): bool
    {
        return $this->mockClient instanceof MockClient;
    }
}
