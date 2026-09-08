<?php

declare(strict_types=1);

namespace Webkul\Bagisto\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Webkul\Bagisto\Contracts\ApiServiceContract;
use Webkul\Bagisto\Enums\Services\ContentType;

/**
 * @internal
 */
final class ApiService implements ApiServiceContract
{
    /**
     * Creates a new Http Transporter instance.
     */
    public function __construct(
        private string $baseUri,
        private Headers $headers,
    ) {}

    public function setBaseUri(string $baseUri): self
    {
        $this->baseUri = $baseUri;

        return $this;
    }

    /**
     * Creates a new Psr 7 Request instance.
     */
    public function toRequest(string $method, string $endpoint, array $payload = [], array $options = []): array
    {
        [$uri, $contentType] = $this->buildUri($endpoint, $options);

        $headers = $this->headers->withContentType($contentType);

        $response = $this->sendRequest($method, $uri, $headers, $payload, $options);

        if ($response->failed()) {
            $errorJson = $response->json();

            if ($response->clientError()) {
                if ($response->status() == 422) {
                    throw ValidationException::withMessages($errorJson['errors'] ?? $errorJson);
                }

                if ($response->status() == 404) {
                    throw ValidationException::withMessages([
                        'endpoint' => 'Endpoint is invalid',
                    ]);
                }

                throw ValidationException::withMessages($errorJson['errors'] ?? $errorJson);
            }

            Log::error('Bagisto API Failure: '.$response->body());
            throw ValidationException::withMessages([
                'message' => $errorJson['message'] ?? $errorJson['errors'] ?? 'Server error from Bagisto: '.$response->status(),
            ]);
        }

        $responseData = $response->json();

        return $responseData['data'] ?? [];
    }

    private function sendRequest($method, $uri, $headers, $payload, $options): Response
    {
        $isMultipart = ! empty($options['isMultipart']) ? true : false;
        $request = $this->initializeRequest($headers, $options['timeout'] ?? 120, $isMultipart);

        if ($isMultipart) {
            $this->attachMedia($request, $payload, $options);
        }

        $payload = $this->preparePayload($payload, $options, $isMultipart);

        return $this->executeRequest($request, $method, $uri, $payload);
    }

    private function initializeRequest($headers, $timeout, $isMultipart): PendingRequest
    {
        $request = Http::withoutVerifying()->withHeaders($headers->toArray())->timeout($timeout);
        if ($isMultipart) {
            $request = $request->asMultipart();
        }

        return $request;
    }

    private function attachMedia($request, &$payload, $options): void
    {
        if (! empty($options['mediaCodes'])) {
            foreach ($options['mediaCodes'] as $mediaCode) {
                if (isset($payload[$mediaCode]) && is_file($payload[$mediaCode])) {
                    $request->attach($mediaCode.'[0]', fopen($payload[$mediaCode], 'r'), basename($payload[$mediaCode]));
                    unset($payload[$mediaCode]);
                }
            }
        }
    }

    private function preparePayload($payload, $options, $isMultipart): array
    {
        $item = [];
        if (! isset($payload['_method']) || ! $isMultipart) {
            return $payload;
        }
        foreach ($payload as $key => $value) {
            if ($key === 'variants') {
                $this->handleVariants($value, $item);

                continue;
            }

            if (is_array($value)) {
                foreach ($value as $newKey => $newValue) {
                    $item[sprintf('%s[%s]', $key, $newKey)] = $newValue;
                }

                continue;
            }

            $item[$key] = $value;
        }

        return $item;
    }

    private function handleVariants($variants, &$item): void
    {
        foreach ($variants as $variantKey => $variantValue) {
            if (is_array($variantValue)) {
                foreach ($variantValue as $newKey => $newValue) {
                    if ($newKey == 'images') {
                        continue;
                    }
                    if (! is_array($newValue)) {
                        $item[sprintf('variants[%s][%s]', $variantKey, $newKey)] = $newValue;
                    }
                }
            } else {
                $item['variants'][$variantKey] = $variantValue;
            }
        }
    }

    private function executeRequest($request, $method, $uri, $payload): Response
    {
        try {
            return $request->$method($uri, $payload);
        } catch (\Exception $e) {
            Log::error($e);

            // Re-throw so the caller receives a real exception it can handle,
            // instead of a null response that later fatals on ->failed().
            throw $e;
        }
    }

    private function buildUri(string $endpoint, $options): array
    {
        $apiEndPointConfig = config('bagisto-api-end-point');

        $configEndpoint = $apiEndPointConfig[$endpoint]['endPoint'];

        if (! empty($options['id'])) {
            $configEndpoint = $apiEndPointConfig[$endpoint]['endPoint'].'/'.$options['id'];
        }

        $contentType = $apiEndPointConfig[$endpoint]['contentType'] ?? ContentType::JSON->value;

        return [
            "{$this->baseUri}/api/v1/admin/{$configEndpoint}",
            $contentType,
        ];
    }
}
