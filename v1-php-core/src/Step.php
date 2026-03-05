<?php declare(strict_types=1);

namespace PhpSaga;

final class Step {
    // Rollback failure strategy constants
    public const RETRY = 1;
    public const LOG_AND_CONTINUE = 2;
    public const ALERT_HUMAN = 3;
    public const THROW = 4;

    private function __construct(
        private readonly string $name,
        private readonly mixed $action,
        private mixed $compensation = null,
        private int $rollbackStrategy = self::LOG_AND_CONTINUE,
    ) {}

    public static function make(callable $action, string $name = ''): self {
        $generatedName = $name;
        if (empty($generatedName)) {
            if (is_string($action)) {
                $generatedName = $action;
            } elseif (is_array($action) && is_string($action[1])) {
                $generatedName = $action[1];
            } else {
                $generatedName = 'unnamed_step';
            }
        }
        return new self($generatedName, $action);
    }

    public function rollback(callable $compensation): self {
        $clone = clone $this;
        $clone->compensation = $compensation;
        return $clone;
    }

    public function onRollbackFailure(int $strategy): self {
        $clone = clone $this;
        $clone->rollbackStrategy = $strategy;
        return $clone;
    }

    /**
     * @throws \Exception
     */
    public function validate(): void {
        if ($this->compensation === null) {
            throw new \Exception("Step '{$this->name}' produces side effects. You MUST define ->rollback()");
        }
    }

    public function getName(): string {
        return $this->name;
    }

    public function getAction(): callable {
        return $this->action;
    }

    public function getCompensation(): ?callable {
        return $this->compensation;
    }

    public function getRollbackStrategy(): int {
        return $this->rollbackStrategy;
    }
}
