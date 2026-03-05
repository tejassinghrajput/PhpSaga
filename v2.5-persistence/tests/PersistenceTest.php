<?php declare(strict_types=1);

namespace PhpSaga\Persistence\Tests;

use PHPUnit\Framework\TestCase;
use PhpSaga\Persistence\DatabaseAuditLogger;
use PDO;

class PersistenceTest extends TestCase {
    public function testCanPersistLogs() {
        $pdo = new PDO('sqlite::memory:');
        $logger = new DatabaseAuditLogger($pdo);
        $logger->createTable();

        $logs = [
            ['event' => 'TEST_EVENT', 'context' => ['foo' => 'bar'], 'timestamp' => microtime(true)]
        ];

        $logger->persist('tx-123', $logs);

        $stmt = $pdo->query('SELECT * FROM saga_audit_logs');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('tx-123', $row['transaction_id']);
        $this->assertEquals('TEST_EVENT', $row['event']);
        $this->assertEquals('{"foo":"bar"}', $row['context']);
    }
}
