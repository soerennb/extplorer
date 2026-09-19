<?php

namespace App\Services;

use RuntimeException;

/**
 * Atomically reserves disk-backed staging resources per owner.
 */
final class StagingResourceService
{
    private string $stateFile;
    private ResourcePolicy $policy;

    public function __construct(?string $stateFile = null, ?ResourcePolicy $policy = null)
    {
        $this->stateFile = $stateFile ?? config('Storage')->runtime . '/staging-reservations.php';
        $this->policy = $policy ?? new ResourcePolicy();
    }

    /**
     * Reserve the desired current size for a staging object. Repeating the
     * call for the same ID replaces its reservation and is concurrency-safe.
     *
     * @param array<string, scalar|null> $metadata
     */
    public function reserve(
        string $owner,
        string $reservationId,
        int $bytes,
        int $files = 0,
        array $metadata = [],
        ?int $expiresAt = null
    ): void {
        $this->assertInput($owner, $reservationId, $bytes, $files);
        $expiresAt ??= time() + $this->policy->uploadStagingTtlSeconds();

        AtomicFileStore::transaction($this->stateFile, function (array &$records) use (
            $owner,
            $reservationId,
            $bytes,
            $files,
            $metadata,
            $expiresAt
        ): void {
            $now = time();
            $this->removeExpiredRecords($records, $now);
            $existing = $records[$reservationId] ?? null;
            if (is_array($existing) && ($existing['owner'] ?? '') !== $owner) {
                throw new RuntimeException('Staging reservation belongs to another user.');
            }

            $ownerBytes = 0;
            $ownerFiles = 0;
            foreach ($records as $id => $record) {
                if ($id === $reservationId || !is_array($record) || ($record['owner'] ?? '') !== $owner) {
                    continue;
                }
                $ownerBytes += (int)($record['bytes'] ?? 0);
                $ownerFiles += (int)($record['files'] ?? 0);
            }

            if ($ownerBytes + $bytes > $this->policy->maxUploadStagingBytes()
                || $ownerFiles + $files > $this->policy->maxUploadStagingFiles()) {
                throw new RuntimeException('Upload staging resource limit exceeded.');
            }

            $records[$reservationId] = [
                'owner' => $owner,
                'bytes' => $bytes,
                'files' => $files,
                'created_at' => is_array($existing) ? (int)($existing['created_at'] ?? $now) : $now,
                'updated_at' => $now,
                'expires_at' => $expiresAt,
                'metadata' => array_merge(is_array($existing['metadata'] ?? null) ? $existing['metadata'] : [], $metadata),
            ];
        });
    }

    public function release(string $reservationId, ?string $owner = null): void
    {
        $this->assertReservationId($reservationId);
        AtomicFileStore::transaction($this->stateFile, function (array &$records) use ($reservationId, $owner): void {
            if (!isset($records[$reservationId])) {
                return;
            }
            if ($owner !== null && ($records[$reservationId]['owner'] ?? '') !== $owner) {
                throw new RuntimeException('Staging reservation belongs to another user.');
            }
            unset($records[$reservationId]);
        });
    }

    /** @return array<string, mixed>|null */
    public function get(string $reservationId): ?array
    {
        $this->assertReservationId($reservationId);
        return AtomicFileStore::transaction($this->stateFile, function (array &$records) use ($reservationId): ?array {
            $record = $records[$reservationId] ?? null;
            if (!is_array($record)) {
                return null;
            }
            if ((int)($record['expires_at'] ?? 0) <= time()) {
                unset($records[$reservationId]);
                return null;
            }

            return $record;
        });
    }

    /** @return list<array<string, mixed>> */
    public function cleanupExpired(): array
    {
        return AtomicFileStore::transaction($this->stateFile, function (array &$records): array {
            $expired = [];
            $now = time();
            foreach ($records as $id => $record) {
                if (!is_array($record) || (int)($record['expires_at'] ?? 0) > $now) {
                    continue;
                }
                $record['id'] = $id;
                $expired[] = $record;
                unset($records[$id]);
            }

            return $expired;
        });
    }

    private function removeExpiredRecords(array &$records, int $now): void
    {
        foreach ($records as $id => $record) {
            if (!is_array($record) || (int)($record['expires_at'] ?? 0) <= $now) {
                unset($records[$id]);
            }
        }
    }

    private function assertInput(string $owner, string $reservationId, int $bytes, int $files): void
    {
        if ($owner === '' || $bytes < 0 || $files < 0) {
            throw new RuntimeException('Invalid staging reservation.');
        }
        $this->assertReservationId($reservationId);
    }

    private function assertReservationId(string $reservationId): void
    {
        if (!preg_match('/\A[A-Za-z0-9_.:-]{1,128}\z/', $reservationId)) {
            throw new RuntimeException('Invalid staging reservation ID.');
        }
    }
}
