from dataclasses import dataclass, field
from typing import Self
from .failure.strategies import RollbackStrategy, AnyCallable

@dataclass(frozen=True)
class Step:
    name: str
    action: AnyCallable
    compensation: AnyCallable | None = field(default=None, repr=False)
    strategy: RollbackStrategy = RollbackStrategy.LOG_AND_CONTINUE

    @classmethod
    def make(cls, action: AnyCallable, name: str = '') -> 'Step':
        return cls(name=name or getattr(action, '__name__', 'unnamed_step'), action=action)

    def rollback(self, fn: AnyCallable) -> Self:
        # Returns new instance (immutable pattern)
        from dataclasses import replace
        return replace(self, compensation=fn)

    def on_rollback_failure(self, strategy: RollbackStrategy) -> Self:
        from dataclasses import replace
        return replace(self, strategy=strategy)
