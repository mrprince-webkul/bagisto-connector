<?php

namespace Webkul\Bagisto\Tests\Unit\Traits;

use Tests\TestCase;
use Webkul\Bagisto\Traits\Mapping;

class MappingTest extends TestCase
{
    private object $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new class
        {
            use Mapping;

            const ENTITY_TYPE = 'product';

            public function parse(mixed $input): array
            {
                return $this->parseIdentifiers($input);
            }
        };
    }

    public function test_parse_identifiers_splits_a_comma_separated_string()
    {
        $this->assertSame(['SKU-1', 'SKU-2'], $this->subject->parse('SKU-1, SKU-2'));
    }

    public function test_parse_identifiers_splits_on_whitespace_and_newlines()
    {
        $this->assertSame(['SKU-1', 'SKU-2', 'SKU-3'], $this->subject->parse("SKU-1 SKU-2\nSKU-3"));
    }

    public function test_parse_identifiers_keeps_the_array_a_tags_field_submits()
    {
        $this->assertSame(['SKU-1', 'SKU-2'], $this->subject->parse([' SKU-1 ', 'SKU-2']));
    }

    public function test_parse_identifiers_drops_empty_entries()
    {
        $this->assertSame(['SKU-2'], $this->subject->parse(' SKU-2 ,, '));
    }

    public function test_parse_identifiers_returns_nothing_for_an_empty_input()
    {
        $this->assertSame([], $this->subject->parse(null));
        $this->assertSame([], $this->subject->parse([]));
    }
}
