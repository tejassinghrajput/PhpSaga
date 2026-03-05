<?php declare(strict_types=1);

namespace PhpSaga\Exception;

class TransactionFailedException extends \Exception {
    /**
     * @param string $stepName
     * @param \Throwable $previous
     * @param array<string, mixed> $context
     */
    public function __construct(
        private readonly string $stepName,
        \Throwable $previous,
        private readonly array $context = []
    ) {
        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
    }

    public function getStepName(): string {
        return $this->stepName;
    }

    public function getContext(): array {
        return $this->context;
    }
}
