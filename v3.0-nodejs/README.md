# PhpSaga Node.js (TypeScript)

Transactional execution for any Node.js function with automatic compensation (Saga Pattern).

## Installation

```bash
npm install phpsaga
```

## Quick Start

```typescript
import { Step, Transaction, RollbackStrategy } from 'phpsaga';

const steps = [
    Step.make(async () => {
        await db.orders.create({ id: 123 });
    }, 'create_order')
    .rollback(async () => {
        await db.orders.delete({ id: 123 });
    }),

    Step.make(async () => {
        await paymentGateway.charge(100);
    }, 'charge_payment')
    .rollback(async () => {
        await paymentGateway.refund(100);
    })
];

const result = await Transaction.run(steps);

if (result.success) {
    console.log("Success!");
} else {
    console.error(`Failed at ${result.failedStep}: ${result.error?.message}`);
}
```

## Features
- **Async/Await Support**: Fully native support for asynchronous actions and compensations.
- **Strictly Typed**: Built with TypeScript for excellent IDE support and safety.
- **Immutable Steps**: Configuration methods return new instances.
- **Reverse Order Rollback**: Only compensates steps that completed successfully.

## Rollback Strategies
- `RollbackStrategy.RETRY`
- `RollbackStrategy.LOG_AND_CONTINUE` (Default)
- `RollbackStrategy.ALERT_HUMAN`
- `RollbackStrategy.THROW`

## API Reference

### `Step`
- `static make(action: Action, name?: string): Step`
- `rollback(compensation: Action): this`
- `onRollbackFailure(strategy: RollbackStrategy): this`

### `Transaction`
- `static async run(steps: Step[]): Promise<TransactionResult>`

### `TransactionResult`
- `success: boolean`
- `auditLog: LogEntry[]`
- `failedStep: string | null`
- `error: Error | null`
