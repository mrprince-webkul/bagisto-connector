<?php

namespace Webkul\Bagisto\Http\Client;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Webkul\Bagisto\Enums\Services\ContentType;
use Webkul\Bagisto\Services\ApiService;
use Webkul\Bagisto\Services\Headers;

final class HttpClientFactory
{
    /**
     * The base URI for the requests.
     */
    private ?string $baseUri = null;

    /**
     * The email used for authentication.
     */
    private ?string $email = null;

    /**
     * The password used for authentication.
     */
    private ?string $password = null;

    /**
     * The HTTP headers for the requests.
     */
    private array $headers = [];

    /**
     * Sets the email for the API requests.
     */
    public function withEmail(string $email): self
    {
        $this->email = trim($email);

        return $this;
    }

    /**
     * Sets the password for the API requests.
     */
    public function withPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Sets the base URI for the API requests.
     * If no URI is provided, the factory will use the default URI.
     */
    public function withBaseUri(string $baseUri): self
    {
        $this->baseUri = $baseUri;

        return $this;
    }

    /**
     * Adds a custom HTTP header to the requests.
     */
    public function withHttpHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Creates and returns an instance of the ApiService.
     * If email and password are provided, it authenticates and includes a token in the headers.
     *
     * @throws \Exception
     * @throws ValidationException
     */
    public function make(): ApiService
    {
        $headers = Headers::create();

        if ($this->email !== null && $this->password !== null) {
            $token = $this->apiAuth($this->email, $this->password);
            $headers = Headers::withAuthorization($token);
        }

        return new ApiService($this->baseUri, $headers);
    }

    /**
     * Authenticates with the provided email and password, and returns a token.
     *
     * @throws ValidationException
     * @throws \Exception
     */
    public function apiAuth(string $email, string $password): string
    {
        $contentType = ContentType::JSON->value;

        $response = Http::withoutVerifying()->withHeaders([
            'Accept' => $contentType,
        ])->post("{$this->baseUri}/api/v1/admin/login", [
            'email'      => $email,
            'password'   => $password,
            'device_name'=> 'api',
        ]);

        if ($response->failed()) {
            if ($response->clientError()) {
                if ($response->status() == 422) {
                    $errorJson = $response->json();
                    throw ValidationException::withMessages($errorJson['errors'] ?? $errorJson);
                }

                if ($response->status() == 404) {
                    throw ValidationException::withMessages([
                        'shop_url' => 'Shop URL is invalid!',
                    ]);
                }

                if ($response->status() == 405) {
                    throw ValidationException::withMessages([
                        'shop_url' => 'The REST API is not installed!',
                    ]);
                }
            }

            throw ValidationException::withMessages([
                'shop_url' => 'Server error',
            ]);
        }

        $data = $response->json();

        if (! isset($data['token'])) {
            throw ValidationException::withMessages([
                'shop_url' => 'Invalid authentication response',
            ]);
        }

        return $data['token'];
    }
}
