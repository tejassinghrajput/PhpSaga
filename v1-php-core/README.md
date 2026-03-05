# PhpSaga v1.0 Core

Transactional execution for any PHP function with automatic compensation (Saga Pattern).

## Installation

```bash
composer require phpsaga/phpsaga
```

## Quick Start: Distributed Transaction with Compensation

The Saga pattern is useful when you have multiple operations that cannot be wrapped in a single database transaction (e.g., calling external APIs).

```php
use PhpSaga\Step;
use PhpSaga\Transaction;

$steps = [
    // Step 1: Create order in local DB
    Step::make(fn() => $db->insert('orders', [...]), 'create_order')
        ->rollback(fn() => $db->delete('orders', ['id' => $orderId])),

    // Step 2: Call external payment API
    Step::make(fn() => $paymentGateway->charge($amount), 'charge_payment')
        ->rollback(fn() => $paymentGateway->refund($transactionId)),

    // Step 3: Send confirmation email (Irreversible - we just log if it fails)
    Step::make(fn() => $mailer->send(...), 'send_email')
        ->onRollbackFailure(Step::LOG_AND_CONTINUE)
];

$result = Transaction::run($steps);

if ($result->wasSuccessful()) {
    echo "Transaction completed successfully!";
} else {
    echo "Transaction failed at step: " . $result->getFailedStep();
    echo "Reason: " . $result->getException()->getMessage();
    // Compensations for all completed steps have already run in reverse order.
}
```

## Operation Types

1.  **Reversible**: Database operations that can be undone by `ROLLBACK` (pass a `PDO` instance to `Transaction::run()`).
2.  **Compensatable**: External operations that need a specific "undo" action (use `->rollback()`).
3.  **Irreversible**: Operations that cannot be easily undone (e.g., sending an email). Use `LOG_AND_CONTINUE` or `ALERT_HUMAN` strategies.

## Rollback Failure Strategies

When a compensation action itself fails, you can choose how to handle it:

- `Step::RETRY`: Automatically retry the compensation up to 3 times with exponential backoff.
- `Step::LOG_AND_CONTINUE` (Default): Log the failure and move to the next compensation in the stack.
- `Step::ALERT_HUMAN`: Log at CRITICAL level and (optionally) fire a custom alert callback.
- `Step::THROW`: Stop everything and throw the exception immediately.

## API Reference

### `PhpSaga\Step`
- `static make(callable $action, string $name = '')`: Create a new step.
- `rollback(callable $compensation)`: Define the undo action.
- `onRollbackFailure(int $strategy)`: Set how to handle compensation failure.

### `PhpSaga\Transaction`
- `static run(array $steps, ?PDO $db = null)`: Execute the saga.

### `PhpSaga\TransactionResult`
- `wasSuccessful(): bool`
- `getAuditLog(): array`
- `getFailedStep(): ?string`
- `getException(): ?Throwable`

## Limitations
- State is not automatically passed between steps. You must handle shared state (e.g., IDs from Step 1 needed in Step 2) via shared variables in your closures.
- It is a sequential runner; parallel execution is not supported in the core version.

## Why not just try/catch?
Try/catch becomes "Arrow Code" very quickly when you have 5+ steps that each need their own specific undo logic. PhpSaga flattens this into a linear list of steps, handling the reverse-order stack for you automatically.
