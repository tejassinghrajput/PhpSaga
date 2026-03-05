<?php declare(strict_types=1);

namespace PhpSaga\Engine;

use PhpSaga\Step;
use PhpSaga\Log\AuditLogger;
use PhpSaga\Exception\TransactionFailedException;

final class ExecutionEngine {
    public function __construct(
        private readonly StateTracker $tracker,
        private readonly AuditLogger $logger
    ) {}

    /**
     * @param list<Step> $steps
     * @throws TransactionFailedException
     * @throws \Exception
     */
    public function execute(array $steps): void {
        foreach ($steps as $index => $step) {
            $step->validate();
            $name = $step->getName();

            $this->logger->log(AuditLogger::STEP_STARTED, ['step' => $name, 'index' => $index]);
            $this->tracker->markStarted($index);

            try {
                $action = $step->getAction();
                $result = $action();

                $this->tracker->markCompleted($index, $result);
                $this->logger->log(AuditLogger::STEP_COMPLETED, ['step' => $name, 'index' => $index]);
            } catch (\Throwable $e) {
                $this->tracker->markFailed($index, $e);
                $this->logger->log(AuditLogger::STEP_FAILED, ['step' => $name, 'index' => $index, 'error' => $e->getMessage()]);
                throw new TransactionFailedException($name, $e);
            }
        }
    }
}
