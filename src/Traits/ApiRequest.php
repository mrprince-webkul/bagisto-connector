<?php

namespace Webkul\Bagisto\Traits;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Webkul\Bagisto\Enums\Export\CacheType;
use Webkul\Bagisto\Http\Client\HttpClientFactory;
use Webkul\Bagisto\Services\ApiService;

trait ApiRequest
{
    protected $httpClient;

    protected $tokenReneratedAt = false;

    /**
     * Validation/error messages from the most recent API call (empty when it succeeded).
     */
    protected array $lastApiErrors = [];

    public function buildHttpRequest(): ApiService
    {
        $this->httpClient = Cache::get(CacheType::BAGISTO_API_HTTP->value);
        if (! $this->httpClient) {
            $httpClientFactory = new HttpClientFactory;
            $this->httpClient = $httpClientFactory->withBaseUri($this->credential['shop_url'])
                ->withEmail($this->credential['email'])
                ->withPassword($this->credential['password'])
                ->make();

            Cache::put(CacheType::BAGISTO_API_HTTP->value, $this->httpClient, config('session.lifetime'));
        }

        return $this->httpClient;
    }

    public function setApiRequest($method, $endPoint, $data = [], array $options = []): ?array
    {
        $this->lastApiErrors = [];

        try {
            $this->buildHttpRequest();
            $response = $this->httpClient->toRequest($method, $endPoint, $data, $options);

            return $response;
        } catch (AuthenticationException $e) {
            if (! $this->tokenReneratedAt) {
                $this->tokenReneratedAt = true;
                $this->buildHttpRequest();

                return $this->setApiRequest($method, $endPoint, $data, $options);
            }

            $this->lastApiErrors = ['authentication' => [$e->getMessage()]];
            $this->logWarning($this->lastApiErrors, $data['sku'] ?? $data['code'] ?? 'bulk');
        } catch (ValidationException $e) {
            $this->lastApiErrors = $e->validator->errors()->messages();
            $this->logWarning($this->lastApiErrors, $data['sku'] ?? $data['code'] ?? 'bulk');
        } catch (\Exception $e) {
            $this->lastApiErrors = ['exception' => [$e->getMessage()]];
            $this->logWarning($this->lastApiErrors, $data['sku'] ?? $data['code'] ?? 'bulk');
        }

        return null;
    }

    public function logWarning(array $data, string $identifier): void
    {
        if (! empty($data) && ! empty($identifier)) {
            $error = json_encode($data, true);

            $this->jobLogger->warning(
                "Warning for item with SKU/Code: {$identifier}, : {$error}"
            );
        }
    }
}
