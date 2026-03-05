<?php declare(strict_types=1);

namespace PhpSaga\Log;

final class AuditLogger {
    public const STEP_STARTED = 'STEP_STARTED';
    public const STEP_COMPLETED = 'STEP_COMPLETED';
    public const STEP_FAILED = 'STEP_FAILED';
    public const COMPENSATION_STARTED = 'COMPENSATION_STARTED';
    public const COMPENSATION_COMPLETED = 'COMPENSATION_COMPLETED';
    public const COMPENSATION_FAILED = 'COMPENSATION_FAILED';
    public const TRANSACTION_COMPLETED = 'TRANSACTION_COMPLETED';
    public const TRANSACTION_ROLLED_BACK = 'TRANSACTION_ROLLED_BACK';

    /** @var list<array{event:string, context:array<string, mixed>, timestamp:float}> */
    private array $logs = [];

    /**
     * @param string $event
     * @param array<string, mixed> $context
     */
    public function log(string $event, array $context = []): void {
        $this->logs[] = [
            'event' => $event,
            'context' => $context,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * @return list<array{event:string, context:array<string, mixed>, timestamp:float}>
     */
    public function getLogs(): array {
        return $this->logs;
    }
}
