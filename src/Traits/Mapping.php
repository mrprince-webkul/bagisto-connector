<?php

namespace Webkul\Bagisto\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

trait Mapping
{
    /**
     * Family groups are keyed by family|group code rather than by record, so an
     * existing row is never reused for them and the lookup is skipped outright.
     */
    protected const GROUP_ENTITY_TYPE = 'groups';

    protected function setMapping(string|int $credentialId, string|int|null $relatedId, string|int|null $externalId, string|int $batchId, ?string $code = null, $entityType = self::ENTITY_TYPE): ?Model
    {
        if ($relatedId === null || $externalId === null) {
            return null;
        }

        $mapping = $entityType === self::GROUP_ENTITY_TYPE
            ? null
            : $this->getMapping($credentialId, $relatedId, null, $code, null, $entityType);

        if (! $mapping) {
            $response = $this->bagistoDataMappingRepository->create([
                'related_id'      => $relatedId,
                'external_id'     => $externalId,
                'code'            => $code,
                'entity_type'     => $entityType,
                'job_instance_id' => $batchId,
                'credential_id'   => $credentialId,
            ]);
        } else {
            $response = $this->bagistoDataMappingRepository->update([
                'external_id'     => $externalId,
                'code'            => $code,
                'job_instance_id' => $batchId,
            ], $mapping->id);
        }

        return $response;
    }

    protected function getMapping(string|int|null $credentialId = null, string|int|null $relatedId = null, string|int|null $externalId = null, ?string $code = null, string|int|null $batchId = null, $entityType = self::ENTITY_TYPE, $type = 'first'): Collection|Model|null
    {
        $query = $this->bagistoDataMappingRepository->where('entity_type', $entityType);

        if ($relatedId) {
            $query->where('related_id', $relatedId);
        }

        if ($externalId) {
            $query->where('external_id', $externalId);
        }

        if ($code) {
            $query->where('code', $code);
        }

        if ($batchId) {
            $query->where('job_instance_id', $batchId);
        }

        if ($credentialId) {
            $query->where('credential_id', $credentialId);
        }

        if ($type === 'get') {
            return $query->get();
        }

        return $query->first();
    }

    protected function parseIdentifiers(mixed $input): array
    {
        $values = is_array($input) ? $input : preg_split('/[\s,]+/', (string) $input);

        return array_values(array_filter(array_map(
            fn ($value): string => trim((string) $value),
            $values ?: []
        )));
    }
}
