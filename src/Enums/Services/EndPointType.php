<?php

namespace Webkul\Bagisto\Enums\Services;

enum EndPointType: string
{
    case GET_CHANNELS = 'getChannels';

    case GET_IS_FILTERABLE_ATTRIBUTES = 'attribute';
}
