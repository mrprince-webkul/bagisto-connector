<?php

namespace Webkul\Bagisto\Helpers\Exporters\Product;

use Webkul\DataTransfer\Helpers\Sources\Export\ProductCursor;

/**
 * Serves an already-resolved set of rows through the cursor contract the core
 * product exporter declares, so the connector keeps its own row selection.
 */
class CollectionCursor extends ProductCursor
{
    public function __construct(protected array $rows)
    {
        parent::__construct([], null);
    }

    protected function fetchNextBatch(): array
    {
        if ($this->offset > 0) {
            return [];
        }

        $this->offset++;

        return $this->rows;
    }

    public function count(): int
    {
        return count($this->rows);
    }
}
