<?php

namespace Webkul\Bagisto\Tests\Unit\Http\Client;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Webkul\Bagisto\Http\Client\HttpClientFactory;

class HttpClientFactoryTest extends TestCase
{
    public function test_request_token_throws_validation_exception_on_failure()
    {
        Http::fake([
            '*' => Http::response(['message' => 'Forbidden'], 403),
        ]);

        $factory = new HttpClientFactory;
        $this->expectException(ValidationException::class);

        $factory->withBaseUri('https://example.com');
        $factory->apiAuth('test@email.com', 'password');
    }

    public function test_request_token_returns_token_on_success()
    {
        Http::fake([
            '*' => Http::response(['token' => 'valid_token_123'], 200),
        ]);

        $factory = new HttpClientFactory;
        $factory->withBaseUri('https://example.com');

        $result = $factory->apiAuth('test@email.com', 'password');

        $this->assertEquals('valid_token_123', $result);
    }
}
