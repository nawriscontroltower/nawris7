<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/shipment_sync_service.php';

/**
 * CLI worker:
 *   php api/process_shipment_sync_jobs.php
 */
if (php_sapi_name() !== 'cli') {
    json_response(['ok' => false, 'message' => 'CLI only endpoint'], 405);
}

$pdo = crm_pdo();
$service = new ShipmentSyncService($pdo);
$maxBatch = (int) ($argv[1] ?? 50);
$processed = 0;

for ($i = 0; $i < $maxBatch; $i++) {
    $pdo->beginTransaction();
    try {
        $job = lockNextPendingJob($pdo);
        if ($job === null) {
            $pdo->commit();
            break;
        }

        $payload = json_decode((string) $job['payload_json'], true);
        if (!is_array($payload)) {
            throw new RuntimeException('Invalid job payload JSON');
        }

        // Domain hook: consume status updates and record KPI events.
        $statusUpdates = $payload['status_updates'] ?? [];
        if (is_array($statusUpdates)) {
            foreach ($statusUpdates as $u) {
                if (!is_array($u)) {
                    continue;
                }
                $service->logKpiEvent([
                    'tracking_code' => $u['tracking_code'] ?? '',
                    'employee_username' => $u['employee_username'] ?? '',
                    'old_status' => $u['old_status'] ?? null,
                    'new_status' => $u['new_status'] ?? '',
                    'delay_days' => $u['delay_days'] ?? null,
                    'processed_at' => $u['processed_at'] ?? (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                    'processing_minutes' => $u['processing_minutes'] ?? 0,
                    'source' => 'api',
                    'metadata' => $u,
                ]);
            }
        }

        markJobDone($pdo, (int) $job['id']);
        $pdo->commit();
        $processed++;
    } catch (Throwable $e) {
        $pdo->rollBack();
        failJob($pdo, isset($job['id']) ? (int) $job['id'] : 0, $e->getMessage());
    }
}

echo "Processed jobs: {$processed}" . PHP_EOL;

function lockNextPendingJob(PDO $pdo): ?array
{
    $stmt = $pdo->query(
        "SELECT * FROM shipment_sync_jobs
         WHERE status = 'pending' AND available_at <= NOW()
         ORDER BY id ASC
         LIMIT 1
         FOR UPDATE"
    );
    $job = $stmt->fetch();
    if (!$job) {
        return null;
    }

    $update = $pdo->prepare(
        "UPDATE shipment_sync_jobs
         SET status = 'processing', attempts = attempts + 1, reserved_at = NOW()
         WHERE id = :id"
    );
    $update->execute([':id' => (int) $job['id']]);

    return $job;
}

function markJobDone(PDO $pdo, int $id): void
{
    if ($id <= 0) {
        return;
    }
    $stmt = $pdo->prepare(
        "UPDATE shipment_sync_jobs
         SET status = 'completed', completed_at = NOW(), last_error = NULL
         WHERE id = :id"
    );
    $stmt->execute([':id' => $id]);
}

function failJob(PDO $pdo, int $id, string $error): void
{
    if ($id <= 0) {
        return;
    }
    $stmt = $pdo->prepare(
        "UPDATE shipment_sync_jobs
         SET status = CASE WHEN attempts >= 5 THEN 'failed' ELSE 'pending' END,
             available_at = DATE_ADD(NOW(), INTERVAL 1 MINUTE),
             last_error = :error
         WHERE id = :id"
    );
    $stmt->execute([
        ':id' => $id,
        ':error' => mb_substr($error, 0, 2000),
    ]);
}
