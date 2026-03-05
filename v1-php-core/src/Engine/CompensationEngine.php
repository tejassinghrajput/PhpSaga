<?php declare(strict_types=1);

namespace PhpSaga\Engine;

use PhpSaga\Step;
use PhpSaga\Log\AuditLogger;
use PhpSaga\Failure\RollbackFailHandler;

final class CompensationEngine {
    public function __construct(
        private readonly StateTracker $tracker,
        private readonly RollbackFailHandler $handler,
        private readonly AuditLogger $logger
    ) {}

    /**
     * @param list<Step> $steps
     */
    public function compensate(array $steps): void {
        $completedIndices = $this->tracker->getCompletedSteps();
        $toCompensate = array_reverse($completedIndices);

        foreach ($toCompensate as $index) {
            $step = $steps[$index] ?? null;
            if (!$step) {
                continue;
            }

            $compensation = $step->getCompensation();
            if ($compensation === null) {
                continue; // pure DB op, auto-handled
            }

            $this->logger->log(AuditLogger::COMPENSATION_STARTED, ['step' => $step->getName(), 'index' => $index]);

            try {
                $compensation();
                $this->logger->log(AuditLogger::COMPENSATION_COMPLETED, ['step' => $step->getName(), 'index' => $index]);
            } catch (\Throwable $e) {
                $this->logger->log(AuditLogger::COMPENSATION_FAILED, [
                    'step' => $step->getName(),
                    'index' => $index,
                    'error' => $e->getMessage()
                ]);
                $this->handler->handle($step, $e);
            }
        }
    }
}
