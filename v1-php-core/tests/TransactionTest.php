<?php declare(strict_types=1);

namespace PhpSaga\Tests;

use PHPUnit\Framework\TestCase;
use PhpSaga\Step;
use PhpSaga\Transaction;
use PhpSaga\Log\AuditLogger;
use PDO;
use Exception;

final class TransactionTest extends TestCase {
    public function testHappyPath(): void {
        $logs = [];
        $step1 = Step::make(function() use (&$logs) { $logs[] = 'step1'; }, 'step1')->rollback(function() {});
        $step2 = Step::make(function() use (&$logs) { $logs[] = 'step2'; }, 'step2')->rollback(function() {});

        $db = $this->createMock(PDO::class);
        $db->expects($this->once())->method('beginTransaction');
        $db->expects($this->once())->method('commit');

        $result = Transaction::run([$step1, $step2], $db);

        $this->assertTrue($result->wasSuccessful());
        $this->assertEquals(['step1', 'step2'], $logs);

        $auditLog = $result->getAuditLog();
        $events = array_column($auditLog, 'event');
        $this->assertContains(AuditLogger::TRANSACTION_COMPLETED, $events);
    }

    public function testFailureOnStep2Of4(): void {
        $logs = [];
        $step1 = Step::make(function() use (&$logs) { $logs[] = 'step1'; }, 'step1')
            ->rollback(function() use (&$logs) { $logs[] = 'comp1'; });
        $step2 = Step::make(function() { throw new Exception('fail'); }, 'step2')
            ->rollback(function() use (&$logs) { $logs[] = 'comp2'; });
        $step3 = Step::make(function() use (&$logs) { $logs[] = 'step3'; }, 'step3')
            ->rollback(function() {});

        $db = $this->createMock(PDO::class);
        $db->expects($this->once())->method('beginTransaction');
        $db->expects($this->once())->method('rollBack');

        $result = Transaction::run([$step1, $step2, $step3], $db);

        $this->assertFalse($result->wasSuccessful());
        $this->assertEquals('step2', $result->getFailedStep());
        // Only step 1 should be compensated. Step 2 failed, step 3 never ran.
        $this->assertEquals(['step1', 'comp1'], $logs);

        $auditLog = $result->getAuditLog();
        $events = array_column($auditLog, 'event');
        $this->assertContains(AuditLogger::TRANSACTION_ROLLED_BACK, $events);
        $this->assertNotContains(AuditLogger::COMPENSATION_STARTED, array_map(fn($l) => $l['event'] === AuditLogger::COMPENSATION_STARTED && $l['context']['step'] === 'step2' ? $l['event'] : '', $auditLog));
    }

    public function testCompensationOrderVerification(): void {
        $logs = [];
        $step1 = Step::make(function() {}, 'step1')->rollback(function() use (&$logs) { $logs[] = 'comp1'; });
        $step2 = Step::make(function() {}, 'step2')->rollback(function() use (&$logs) { $logs[] = 'comp2'; });
        $step3 = Step::make(function() {}, 'step3')->rollback(function() use (&$logs) { $logs[] = 'comp3'; });
        $step4 = Step::make(function() { throw new Exception('fail'); }, 'step4')->rollback(function() {});

        $result = Transaction::run([$step1, $step2, $step3, $step4]);

        $this->assertFalse($result->wasSuccessful());
        // Reverse order: 3, 2, 1
        $this->assertEquals(['comp3', 'comp2', 'comp1'], $logs);
    }

    public function testRetryStrategy(): void {
        $attempts = 0;
        $step1 = Step::make(function() {}, 'step1')
            ->rollback(function() use (&$attempts) {
                $attempts++;
                throw new Exception('comp fail');
            })
            ->onRollbackFailure(Step::RETRY);

        $step2 = Step::make(function() { throw new Exception('fail'); }, 'step2')->rollback(function() {});

        $result = Transaction::run([$step1, $step2]);

        // 1 original call (in ExecutionEngine, oh wait, compensation is in CompensationEngine)
        // CompensationEngine calls once, then handler retries 3 times if it fails.
        // Total should be 4 calls to the compensation callable.
        $this->assertEquals(4, $attempts);
    }

    public function testLogAndContinue(): void {
        $logs = [];
        $step1 = Step::make(function() {}, 'step1')->rollback(function() use (&$logs) { $logs[] = 'comp1'; });
        $step2 = Step::make(function() {}, 'step2')
            ->rollback(function() { throw new Exception('comp2 fail'); })
            ->onRollbackFailure(Step::LOG_AND_CONTINUE);
        $step3 = Step::make(function() { throw new Exception('fail'); }, 'step3')->rollback(function() {});

        $result = Transaction::run([$step1, $step2, $step3]);

        $this->assertFalse($result->wasSuccessful());
        // Step 2 compensation fails but should continue to step 1
        $this->assertEquals(['comp1'], $logs);

        $auditLog = $result->getAuditLog();
        $events = array_column($auditLog, 'event');
        $this->assertContains(AuditLogger::COMPENSATION_FAILED, $events);
    }

    public function testStepWithoutRollbackThrows(): void {
        $step1 = Step::make(function() { return 'side-effect'; }, 'step1');

        $result = Transaction::run([$step1]);

        $this->assertFalse($result->wasSuccessful());
        $this->assertStringContainsString('side effects', $result->getException()->getMessage());
    }
}
