<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Queue-oriented sync service for external API shipment updates.
 */
final class ShipmentSyncService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function enqueue(array $payload): string
    {
        $jobUuid = $this->uuidV4();
        $stmt = $this->pdo->prepare(
            'INSERT INTO shipment_sync_jobs (job_uuid, payload_json, status, available_at) VALUES (:uuid, :payload, :status, :available_at)'
        );
        $stmt->execute([
            ':uuid' => $jobUuid,
            ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':status' => 'pending',
            ':available_at' => (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
        ]);

        return $jobUuid;
    }

    public function logKpiEvent(array $event): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO shipment_kpi_events
            (tracking_code, employee_username, old_status, new_status, delay_days, processed_at, processing_minutes, source, metadata_json)
            VALUES
            (:tracking_code, :employee_username, :old_status, :new_status, :delay_days, :processed_at, :processing_minutes, :source, :metadata_json)'
        );
        $stmt->execute([
            ':tracking_code' => (string) ($event['tracking_code'] ?? ''),
            ':employee_username' => (string) ($event['employee_username'] ?? ''),
            ':old_status' => $event['old_status'] ?? null,
            ':new_status' => (string) ($event['new_status'] ?? ''),
            ':delay_days' => isset($event['delay_days']) ? (int) $event['delay_days'] : null,
            ':processed_at' => (string) ($event['processed_at'] ?? (new DateTimeImmutable())->format('Y-m-d H:i:s')),
            ':processing_minutes' => max(0, (int) ($event['processing_minutes'] ?? 0)),
            ':source' => (string) ($event['source'] ?? 'api'),
            ':metadata_json' => json_encode($event['metadata'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (php_sapi_name() !== 'cli' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $raw = file_get_contents('php://input') ?: '{}';
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        json_response(['ok' => false, 'message' => 'Invalid JSON payload'], 422);
    }

    $service = new ShipmentSyncService(crm_pdo());
    $jobUuid = $service->enqueue($payload);
    json_response(['ok' => true, 'job_uuid' => $jobUuid]);
}
