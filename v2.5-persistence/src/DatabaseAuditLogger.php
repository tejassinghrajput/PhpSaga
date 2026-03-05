<?php declare(strict_types=1);

namespace PhpSaga\Persistence;

use PDO;

final class DatabaseAuditLogger {
    public function __construct(private readonly PDO $db) {}

    public function persist(string $transactionId, array $logs): void {
        $stmt = $this->db->prepare('INSERT INTO saga_audit_logs (transaction_id, event, context, timestamp) VALUES (?, ?, ?, ?)');

        foreach ($logs as $log) {
            $stmt->execute([
                $transactionId,
                $log['event'],
                json_encode($log['context']),
                $log['timestamp']
            ]);
        }
    }

    public function createTable(): void {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS saga_audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                transaction_id TEXT NOT NULL,
                event TEXT NOT NULL,
                context TEXT,
                timestamp REAL NOT NULL
            )
        ');
    }
}
