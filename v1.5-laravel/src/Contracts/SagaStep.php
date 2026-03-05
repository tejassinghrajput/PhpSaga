<?php declare(strict_types=1);

namespace PhpSaga\Laravel\Contracts;

interface SagaStep {
    /**
     * The main action — called by the engine
     */
    public function execute(): mixed;

    /**
     * Compensation — called on failure in reverse order
     * MUST be idempotent
     */
    public function compensate(): void;

    /**
     * Human-readable step identifier for audit log
     */
    public function getStepName(): string;

    /**
     * One of Step::RETRY|LOG_AND_CONTINUE|ALERT_HUMAN|THROW
     */
    public function getCompensationFailureStrategy(): int;
}
