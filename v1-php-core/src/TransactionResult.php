<?php declare(strict_types=1);

namespace PhpSaga;

final class TransactionResult {
    /**
     * @param bool $success
     * @param list<array{event:string, context:array<string, mixed>, timestamp:float}> $auditLog
     * @param ?string $failedStep
     * @param ?\Throwable $exception
     */
    public function __construct(
        private readonly bool $success,
        private readonly array $auditLog,
        private readonly ?string $failedStep = null,
        private readonly ?\Throwable $exception = null
    ) {}

    public static function success(array $auditLog): self {
        return new self(true, $auditLog);
    }

    public static function failure(\Throwable $e, array $auditLog, ?string $failedStep = null): self {
        return new self(false, $auditLog, $failedStep, $e);
    }

    public function wasSuccessful(): bool {
        return $this->success;
    }

    public function getAuditLog(): array {
        return $this->auditLog;
    }

    public function getFailedStep(): ?string {
        return $this->failedStep;
    }

    public function getException(): ?\Throwable {
        return $this->exception;
    }
}
