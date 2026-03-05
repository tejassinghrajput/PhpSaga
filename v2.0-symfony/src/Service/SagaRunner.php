<?php declare(strict_types=1);

namespace PhpSaga\Symfony\Service;

use PhpSaga\Transaction;
use PhpSaga\TransactionResult;
use Psr\Log\LoggerInterface;
use Throwable;

class SagaRunner {
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array $steps
     * @return TransactionResult
     * @throws Throwable
     */
    public function run(array $steps): TransactionResult {
        try {
            $result = Transaction::run($steps);
            if (!$result->wasSuccessful()) {
                $this->logger->error('PhpSaga transaction failed', [
                    'step' => $result->getFailedStep(),
                    'error' => $result->getException()?->getMessage()
                ]);
            }
            return $result;
        } catch (Throwable $e) {
            $this->logger->critical('PhpSaga transaction failed with unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
