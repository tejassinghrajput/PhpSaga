from enum import Enum
from typing import Callable, Any, Awaitable, Union

class RollbackStrategy(Enum):
    RETRY = "retry"
    LOG_AND_CONTINUE = "log_and_continue"
    ALERT_HUMAN = "alert_human"
    RAISE = "raise"

# Type alias for callables that can be sync or async
SyncCallable = Callable[[], Any]
AsyncCallable = Callable[[], Awaitable[Any]]
AnyCallable = Union[SyncCallable, AsyncCallable]
