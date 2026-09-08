<?php

namespace Webkul\Bagisto\Traits;

/**
 * Adds an "updated" dimension to the per-batch export summary for the Job Tracker.
 */
trait ExportSummary
{
    /**
     * Returns number of updated items count.
     */
    public function getUpdatedItemsCount(): int
    {
        $count = $this->export->summary['updated'] ?? 0;

        return $count + $this->updatedItemsCount;
    }

    /**
     * Persist the per-batch summary including the updated count.
     */
    public function updateBatchState(int $id, string $state): void
    {
        if (method_exists($this, 'heartbeat')) {
            $this->heartbeat(true);
        }

        $processed = $this->getCreatedItemsCount() + $this->getUpdatedItemsCount() - $this->getSkippedtemsCount();

        $this->exportBatchRepository->update([
            'state'   => $state,
            'summary' => [
                'processed' => $processed < 0 ? 0 : $processed,
                'created'   => $this->getCreatedItemsCount(),
                'updated'   => $this->getUpdatedItemsCount(),
                'skipped'   => $this->getSkippedtemsCount(),
            ],
        ], $id);
    }
}
