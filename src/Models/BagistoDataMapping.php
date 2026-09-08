<?php

namespace Webkul\Bagisto\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Bagisto\Contracts\BagistoDataMapping as BagistoDataMappingContract;

class BagistoDataMapping extends Model implements BagistoDataMappingContract
{
    protected $table = 'wk_bagisto_data_mapping';

    protected $fillable = [
        'entity_type',
        'external_id',
        'code',
        'job_instance_id',
        'related_id',
        'credential_id',
    ];
}
