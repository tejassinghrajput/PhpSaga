<?php declare(strict_types=1);

namespace PhpSaga\Engine;

final class StateTracker {
    /** @var array<int, array{status:string, result:mixed, error:?\Throwable}> */
    private array $state = [];
    /** @var list<int> */
    private array $order = [];

    public function markStarted(int $index): void {
        $this->state[$index] = ['status' => 'started', 'result' => null, 'error' => null];
    }

    public function markCompleted(int $index, mixed $result = null): void {
        $this->state[$index] = ['status' => 'completed', 'result' => $result, 'error' => null];
        $this->order[] = $index;
    }

    public function markFailed(int $index, \Throwable $e): void {
        $this->state[$index] = ['status' => 'failed', 'result' => null, 'error' => $e];
    }

    /**
     * Returns step indices in COMPLETION ORDER — used by CompensationEngine
     * @return list<int>
     */
    public function getCompletedSteps(): array {
        return $this->order;
    }

    public function wasStepCompleted(string $name): bool {
        return ($this->state[$name]['status'] ?? '') === 'completed';
    }

    /**
     * @return ?array{name:string, error:\Throwable}
     */
    public function getFailedStep(): ?array {
        foreach ($this->state as $name => $data) {
            if ($data['status'] === 'failed' && $data['error'] instanceof \Throwable) {
                return ['name' => $name, 'error' => $data['error']];
            }
        }
        return null;
    }

    public function reset(): void {
        $this->state = [];
        $this->order = [];
    }
}
