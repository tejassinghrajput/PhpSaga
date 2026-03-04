import asyncio
from dataclasses import dataclass
from typing import Any
from .step import Step
from .failure.strategies import RollbackStrategy
from .engine.state_tracker import StateTracker
from .engine.execution import ExecutionEngine, SimpleLogger, _call

@dataclass(frozen=True)
class TransactionResult:
    success: bool
    audit_log: tuple[tuple[str, str, dict[str, Any] | None], ...] # immutable — frozen dataclass
    failed_step: str | None = None
    error: Exception | None = None

class Transaction:
    @staticmethod
    def run(steps: list[Step], db: Any = None) -> TransactionResult:
        # Sync entry point — runs async engine in a new event loop
        # ONLY if not already in an event loop (avoids RuntimeError)
        try:
            asyncio.get_running_loop()
            # Already in async context — raise helpful error
            raise RuntimeError('Use Transaction.run_async() in async context')
        except RuntimeError as e:
            if str(e) == 'Use Transaction.run_async() in async context':
                raise
            return asyncio.run(Transaction._run(steps, db))

    @staticmethod
    async def run_async(steps: list[Step], session: Any = None) -> TransactionResult:
        return await Transaction._run(steps, session)

    @staticmethod
    async def _run(steps: list[Step], db: Any = None) -> TransactionResult:
        tracker = StateTracker()
        logger = SimpleLogger()
        engine = ExecutionEngine(tracker, logger)

        failed_step = None
        error = None

        try:
            await engine.execute(steps)
            success = True
        except Exception as e:
            success = False
            error = e
            # Find the failed step name
            completed = tracker.get_completed_steps()
            # If engine.execute failed at the first step, completed might be empty
            # The failed step is the one after the completed ones
            if len(completed) < len(steps):
                failed_step = steps[len(completed)].name

            # Rollback in reverse order of completion
            for step_name in reversed(completed):
                # Find the step object by name
                step = next(s for s in steps if s.name == step_name)
                if step.compensation:
                    try:
                        await _call(step.compensation)
                        logger.log('COMPENSATION_COMPLETED', step.name)
                    except Exception as comp_error:
                        logger.log('COMPENSATION_FAILED', step.name, {'error': str(comp_error)})
                        if step.strategy == RollbackStrategy.RAISE:
                            raise comp_error

        return TransactionResult(
            success=success,
            audit_log=tuple(logger.logs),
            failed_step=failed_step,
            error=error
        )
