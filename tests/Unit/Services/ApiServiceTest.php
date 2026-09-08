<?php

namespace Webkul\Bagisto\Tests\Unit\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Webkul\Bagisto\Services\ApiService;
use Webkul\Bagisto\Services\Headers;

class ApiServiceTest extends TestCase
{
    private $apiService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('bagisto-api-end-point', [
            'test' => [
                'endPoint'    => 'test',
                'contentType' => 'application/json',
            ],
        ]);

        $this->apiService = new ApiService('https://example.com', Headers::create());
    }

    public function test_to_request_throws_validation_exception_on_server_error()
    {
        Http::fake([
            '*' => Http::response(['message' => 'Internal Server Error'], 500),
        ]);

        $this->expectException(ValidationException::class);
        $this->apiService->toRequest('get', 'test');
    }

    public function test_to_request_throws_validation_exception_on_client_error()
    {
        Http::fake([
            '*' => Http::response(['errors' => ['field' => 'Invalid']], 422),
        ]);

        $this->expectException(ValidationException::class);
        $this->apiService->toRequest('post', 'test');
    }

    public function test_to_request_rethrows_connection_exception_instead_of_crashing_on_null()
    {
        // A network failure (timeout, DNS, refused) must surface as a real exception
        // the job can handle — not be swallowed to null and crash on ->failed().
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $this->expectException(ConnectionException::class);
        $this->apiService->toRequest('get', 'test');
    }
}
