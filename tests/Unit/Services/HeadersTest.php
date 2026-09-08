<?php

namespace Webkul\Bagisto\Tests\Unit\Services;

use Tests\TestCase;
use Webkul\Bagisto\Services\Headers;

class HeadersTest extends TestCase
{
    public function test_with_content_type_adds_application_json_by_default()
    {
        $headers = Headers::withAuthorization('token');

        $newHeaders = $headers->withContentType();
        $array = $newHeaders->toArray();

        $this->assertArrayHasKey('accept', $array);
        $this->assertEquals('application/json', $array['accept']);
        $this->assertArrayHasKey('Content-Type', $array);
        $this->assertEquals('application/json', $array['Content-Type']);
        $this->assertArrayHasKey('Authorization', $array);
    }

    public function test_with_content_type_allows_override()
    {
        $headers = Headers::create();

        $newHeaders = $headers->withContentType('multipart/form-data');
        $array = $newHeaders->toArray();

        $this->assertEquals('multipart/form-data', $array['Content-Type']);
        $this->assertEquals('application/json', $array['accept']);
    }
}
