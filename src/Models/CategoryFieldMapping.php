<?php

namespace Webkul\Bagisto\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Bagisto\Contracts\CategoryFieldMapping as CategoryFieldMappingContract;
use Webkul\Bagisto\Presenters\JsonDataPresenter;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;

class CategoryFieldMapping extends Model implements CategoryFieldMappingContract, HistoryContract, PresentableHistoryInterface
{
    use HistoryTrait;

    protected $historyTags = ['bagitsto_category_field_mapping'];

    /**
     * The database table used by model
     *
     * @var string
     */
    protected $table = 'wk_bagisto_category_field_config_mapping';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'section',
        'mapped_value',
        'fixed_value',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'mapped_value' => 'json',
        'fixed_value'  => 'json',
    ];

    public static function getPresenters(): array
    {
        return [
            'mapped_value' => JsonDataPresenter::class,
            'fixed_value'  => JsonDataPresenter::class,
        ];
    }
}
