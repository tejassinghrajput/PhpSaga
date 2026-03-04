from dataclasses import dataclass
from typing import Any, Literal

@dataclass
class StepState:
    name: str
    status: Literal['pending', 'completed', 'failed']
    result: Any = None
    error: Exception | None = None

class StateTracker:
    def __init__(self) -> None:
        self._state: dict[str, StepState] = {}
        self._order: list[str] = [] # completion order

    def mark_completed(self, name: str, result: Any = None) -> None:
        self._state[name] = StepState(name, 'completed', result)
        self._order.append(name)

    def mark_failed(self, name: str, error: Exception) -> None:
        self._state[name] = StepState(name, 'failed', error=error)

    def get_completed_steps(self) -> list[str]:
        return list(self._order) # in completion order

    def was_completed(self, name: str) -> bool:
        return self._state.get(name, StepState('', 'pending')).status == 'completed'
