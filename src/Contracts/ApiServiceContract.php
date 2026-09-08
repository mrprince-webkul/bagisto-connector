<?php

declare(strict_types=1);

namespace Webkul\Bagisto\Contracts;

/**
 * @internal
 */
interface ApiServiceContract
{
    /**
     * Sends a content request to a server.
     */
    public function toRequest(string $method, string $endpoint, array $payload = [], array $options = []): array;
}
