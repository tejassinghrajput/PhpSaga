<?php declare(strict_types=1);

namespace PhpSaga\Failure;

use PhpSaga\Step;
use PhpSaga\Log\AuditLogger;

final class RollbackFailHandler {
    /** @var ?callable */
    private $alertCallback = null;

    public function __construct(private readonly AuditLogger $logger) {}

    public function registerAlertCallback(callable $callback): void {
        $this->alertCallback = $callback;
    }

    /**
     * @throws \Throwable
     */
    public function handle(Step $step, \Throwable $e): void {
        $strategy = $step->getRollbackStrategy();
        $name = $step->getName();

        switch ($strategy) {
            case Step::RETRY:
                $this->handleRetry($step, $e);
                break;
            case Step::LOG_AND_CONTINUE:
                $this->logger->log(AuditLogger::COMPENSATION_FAILED, [
                    'step' => $name,
                    'error' => $e->getMessage()
                ]);
                break;
            case Step::ALERT_HUMAN:
                $this->logger->log(AuditLogger::COMPENSATION_FAILED, [
                    'step' => $name,
                    'error' => $e->getMessage(),
                    'severity' => 'CRITICAL'
                ]);
                if ($this->alertCallback) {
                    ($this->alertCallback)($step, $e);
                }
                break;
            case Step::THROW:
                throw $e;
        }
    }

    private function handleRetry(Step $step, \Throwable $e): void {
        $attempts = 0;
        $maxAttempts = 3;
        $delays = [100000, 200000, 400000]; // microseconds

        while ($attempts < $maxAttempts) {
            usleep($delays[$attempts]);
            try {
                $compensation = $step->getCompensation();
                $compensation();
                return; // success
            } catch (\Throwable $retryError) {
                $attempts++;
            }
        }

        $this->logger->log(AuditLogger::COMPENSATION_FAILED, [
            'step' => $step->getName(),
            'error' => "Retried {$maxAttempts} times, all failed. Original: " . $e->getMessage()
        ]);
    }
}
