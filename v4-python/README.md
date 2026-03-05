# PhpSaga Python

Transactional execution for Python functions with automatic compensation (Saga Pattern). Supports both sync and async callables.

## Installation

```bash
pip install phpsaga
```

## Quick Start

```python
import asyncio
from phpsaga import Step, Transaction

async def main():
    steps = [
        Step.make(lambda: print("Step 1 done"), "step1")
            .rollback(lambda: print("Step 1 undone")),

        Step.make(async_action, "step2")
            .rollback(async_compensation),
    ]

    result = await Transaction.run_async(steps)

    if result.success:
        print("All steps completed!")
    else:
        print(f"Failed at {result.failed_step}")

asyncio.run(main())
```

## Features
- **Transparent Sync/Async**: Mix synchronous and asynchronous functions in the same saga.
- **Dual Entry Points**: Use `Transaction.run()` for sync contexts and `await Transaction.run_async()` for async ones.
- **Framework Integrations**: Built-in support for Django and FastAPI.
- **Strict Typing**: Passes `mypy --strict`.

## Framework Integrations

### Django
Use the `@transactional` decorator to wrap function-based views in a DB transaction:

```python
from phpsaga.django import transactional

@transactional
def my_view(request):
    # This view runs inside a django.db.transaction.atomic() block
    ...
```

### FastAPI
Add the `TransactionalMiddleware` to your application:

```python
from fastapi import FastAPI
from phpsaga.fastapi import TransactionalMiddleware

app = FastAPI()
app.add_middleware(TransactionalMiddleware)
```

## Rollback Strategies
- `RollbackStrategy.RETRY`
- `RollbackStrategy.LOG_AND_CONTINUE` (Default)
- `RollbackStrategy.ALERT_HUMAN`
- `RollbackStrategy.RAISE`

## API Reference

### `Step`
- `static make(action: AnyCallable, name: str = ''): Step`
- `rollback(fn: AnyCallable): Step`
- `on_rollback_failure(strategy: RollbackStrategy): Step`

### `Transaction`
- `static run(steps: list[Step], db: Any = None) -> TransactionResult`
- `static async run_async(steps: list[Step], session: Any = None) -> TransactionResult`
