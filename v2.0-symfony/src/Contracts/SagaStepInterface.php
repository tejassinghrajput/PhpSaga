<?php declare(strict_types=1);

namespace PhpSaga\Symfony\Contracts;

interface SagaStepInterface {
    /**
     * The main action — called by SagaRunner
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
