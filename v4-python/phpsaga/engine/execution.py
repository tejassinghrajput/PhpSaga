import asyncio
import inspect
from typing import Any, Protocol
from ..step import Step
from .state_tracker import StateTracker

# Core helper: run any callable, sync or async
async def _call(fn: Any) -> Any:
    if inspect.iscoroutinefunction(fn):
        return await fn()
    return fn() # sync callable called directly in async context

class Logger(Protocol):
    def log(self, event: str, name: str, data: dict[str, Any] | None = None) -> None: ...

class SimpleLogger:
    def __init__(self) -> None:
        self.logs: list[tuple[str, str, dict[str, Any] | None]] = []

    def log(self, event: str, name: str, data: dict[str, Any] | None = None) -> None:
        self.logs.append((event, name, data))

class ExecutionEngine:
    def __init__(self, tracker: StateTracker, logger: Logger) -> None:
        self.tracker = tracker
        self.logger = logger

    async def execute(self, steps: list[Step]) -> None:
        for index, step in enumerate(steps): # SEQUENTIAL — not asyncio.gather()
            try:
                result = await _call(step.action)
                self.tracker.mark_completed(index, step.name, result)
                self.logger.log('STEP_COMPLETED', step.name, {'index': index})
            except Exception as e:
                self.tracker.mark_failed(index, step.name, e)
                self.logger.log('STEP_FAILED', step.name, {'index': index, 'error': str(e)})
                raise # stop execution
