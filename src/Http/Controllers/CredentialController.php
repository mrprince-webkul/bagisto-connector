<?php

namespace Webkul\Bagisto\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Bagisto\DataGrids\CredentialDataGrid;
use Webkul\Bagisto\Enums\Export\CacheType;
use Webkul\Bagisto\Enums\Services\EndPointType;
use Webkul\Bagisto\Enums\Services\MethodType;
use Webkul\Bagisto\Http\Client\HttpClientFactory;
use Webkul\Bagisto\Http\Requests\CredentialCreateRequest;
use Webkul\Bagisto\Http\Requests\CredentialUpdateRequest;
use Webkul\Bagisto\Repositories\CredentialRepository;
use Webkul\Bagisto\Traits\EncryptableTrait;
use Webkul\Core\Repositories\ChannelRepository;

class CredentialController extends Controller
{
    use EncryptableTrait;

    public function __construct(
        protected ChannelRepository $channelRepository,
        protected CredentialRepository $credentialRepository
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(CredentialDataGrid::class)->toJson();
        }

        return view('bagisto::credentials.index');
    }

    /**
     * Store a credential.
     */
    public function store(CredentialCreateRequest $request): JsonResponse
    {
        $requestData = $request->only([
            'email',
            'password',
            'shop_url',
        ]);

        $httpClient = new HttpClientFactory;
        $requestData['shop_url'] = rtrim($requestData['shop_url'], '/');

        try {
            $httpClient = $httpClient->withBaseUri($requestData['shop_url'])
                ->withEmail($requestData['email'])
                ->withPassword($requestData['password'])
                ->make();

            $requestData['password'] = $this->encryptValue($requestData['password']);

            $responseData = $this->credentialRepository->create($requestData);

            return new JsonResponse([
                'message'      => trans('bagisto::app.bagisto.credentials.index.create-success'),
                'redirect_url' => route('admin.bagisto.credentials.edit', $responseData->id),
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse([
                'errors' => $e->validator->errors(),
            ], 422);
        }
    }

    /**
     * Display the specified resource for editing.
     *
     *
     * @throws ModelNotFoundException If the credential with the given ID is not found.
     */
    public function edit(int $id): View
    {
        $credential = $this->credentialRepository->find($id);

        if (! $credential) {
            abort(404);
        }

        try {
            $httpClient = new HttpClientFactory;
            $httpClient = $httpClient->withBaseUri($credential->shop_url)
                ->withEmail($credential->email)
                ->withPassword($this->decryptValue($credential->password))
                ->make();
        } catch (\Exception $e) {
            session()->flash('credential', trans('bagisto::app.bagisto.credentials.index.invalid'));
        }
        if (in_array('toRequest', get_class_methods($httpClient))) {
            $storeChannels = $httpClient->toRequest(MethodType::GET->value, EndPointType::GET_CHANNELS->value);
            $storefilterableAttribtes = $httpClient->toRequest(MethodType::GET->value, EndPointType::GET_IS_FILTERABLE_ATTRIBUTES->value, ['is_filterable' => 1]);
        } else {
            $storeChannels = [];
            $storefilterableAttribtes = [];
        }

        $channels = $this->channelRepository->all();

        $credential->store_info = array_map(function ($channel) {
            return json_decode($channel);
        }, $credential->store_info ?? []);

        $unoPimChannels = [];

        foreach ($channels as $channel) {
            $unoPimChannels[] = [
                'id'         => $channel->id,
                'name'       => ! empty($channel->name) ? $channel->name : $channel->code,
                'code'       => $channel->code,
                'currencies' => $channel->currencies->toArray(),
                'locales'    => $channel->locales->toArray(),
            ];
        }

        return view('bagisto::credentials.edit', compact('unoPimChannels', 'storeChannels', 'storefilterableAttribtes', 'credential'));
    }

    public function update(CredentialUpdateRequest $request, $id): JsonResponse
    {
        $additional = [];
        if ($request->filterableAttribtes) {
            $additional = ['additional_info' => [['filterableAttribtes' => $request->filterableAttribtes]]];
        }
        $password = $request->password;
        $credential = $this->credentialRepository->findWhere(['shop_url' => $request->shop_url, 'email' => $request->email])->first();

        if ($request->password === $credential->password) {
            $password = $this->decryptValue($credential->password);
            $additional['password'] = $credential->password;
        }

        $httpClient = new HttpClientFactory;
        $httpClient = $httpClient->withBaseUri($request->shop_url)
            ->withEmail($request->email)
            ->withPassword($password)
            ->make();

        $requestData = $request->only([
            'email',
            'password',
            'store_info',
        ]);

        $requestData['password'] = $this->encryptValue($password);
        $requestData['store_info'] = $this->sanitizeStoreInfo($requestData['store_info'] ?? []);
        $requestData = array_merge($requestData, $additional);

        $this->credentialRepository->update($requestData, $id);

        Cache::forget(CacheType::CREDENTIAL->value);
        Cache::forget(CacheType::PRODUCT_JOB_FILTERS->value);
        Cache::forget(CacheType::CATEGORY_JOB_FILTERS->value);

        return new JsonResponse([
            'message'      => trans('bagisto::app.bagisto.credentials.index.update-success'),
            'redirect_url' => route('admin.bagisto.credentials.edit', $id),
        ]);
    }

    /**
     * The edit form re-serialises whatever it loaded, so an empty or repeated
     * mapping saved once keeps coming back and grows on every save.
     */
    protected function sanitizeStoreInfo($storeInfo): array
    {
        $clean = [];

        foreach ((array) $storeInfo as $channelId => $mapping) {
            if (! is_string($mapping) || trim($mapping) === '') {
                continue;
            }

            $decoded = json_decode($mapping, true);

            if (! is_array($decoded) || $decoded === [] || empty($decoded['channel'])) {
                continue;
            }

            $clean[$channelId] = $mapping;
        }

        return $clean;
    }

    public function destroy($id): JsonResponse
    {
        $this->credentialRepository->delete($id);

        return new JsonResponse(['message' => trans('bagisto::app.bagisto.credentials.index.delete-success')]);
    }
}
