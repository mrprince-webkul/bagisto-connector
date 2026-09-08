<?php

namespace Webkul\Bagisto\Enums\Services;

enum ContentType: string
{
    case JSON = 'application/json';

    case MULTIPART = 'multipart/form-data';
}
