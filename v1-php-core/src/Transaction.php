<?php declare(strict_types=1);

namespace PhpSaga;

use PhpSaga\Engine\StateTracker;
use PhpSaga\Log\AuditLogger;
use PhpSaga\Engine\ExecutionEngine;
use PhpSaga\Engine\CompensationEngine;
use PhpSaga\Failure\RollbackFailHandler;
use PhpSaga\Exception\TransactionFailedException;
use PDO;

final class Transaction {
    /**
     * @param list<Step> $steps
     * @param ?PDO $db
     * @return TransactionResult
     */
    public static function run(array $steps, ?PDO $db = null): TransactionResult {
        $tracker = new StateTracker();
        $logger = new AuditLogger();
        $failHandler = new RollbackFailHandler($logger);

        $executionEngine = new ExecutionEngine($tracker, $logger);
        $compensationEngine = new CompensationEngine($tracker, $failHandler, $logger);

        if ($db) {
            $db->beginTransaction();
        }

        try {
            $executionEngine->execute($steps);

            if ($db) {
                $db->commit();
            }

            $logger->log(AuditLogger::TRANSACTION_COMPLETED);
            return TransactionResult::success($logger->getLogs());
        } catch (TransactionFailedException $e) {
            $compensationEngine->compensate($steps);

            if ($db) {
                $db->rollBack();
            }

            $logger->log(AuditLogger::TRANSACTION_ROLLED_BACK, ['failed_step' => $e->getStepName()]);
            return TransactionResult::failure($e->getPrevious() ?: $e, $logger->getLogs(), $e->getStepName());
        } catch (\Throwable $e) {
            // General failure, maybe validate() failed or something else before ExecutionEngine could wrap it
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return TransactionResult::failure($e, $logger->getLogs());
        }
    }
}
