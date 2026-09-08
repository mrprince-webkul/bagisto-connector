<?php

namespace Webkul\Bagisto\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Bagisto\Contracts\Credential as CredentialContract;
use Webkul\Bagisto\Presenters\JsonDataPresenter;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;

class Credential extends Model implements CredentialContract, HistoryContract, PresentableHistoryInterface
{
    use HistoryTrait;

    protected $historyTags = ['bagitsto_credentials'];

    /**
     * The database table used by model
     *
     * @var string
     */
    protected $table = 'wk_bagisto_credential';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'shop_url',
        'email',
        'password',
        'store_info',
        'additional_info',
    ];

    protected $casts = [
        'store_info'      => 'json',
        'additional_info' => 'json',
    ];

    public static function getPresenters(): array
    {
        return [
            'store_info'      => JsonDataPresenter::class,
            'additional_info' => JsonDataPresenter::class,
        ];
    }
}
