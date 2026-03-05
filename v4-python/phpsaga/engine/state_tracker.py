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
        self._state: dict[int, StepState] = {}
        self._order: list[int] = [] # completion order (indices)

    def mark_completed(self, index: int, name: str, result: Any = None) -> None:
        self._state[index] = StepState(name, 'completed', result)
        self._order.append(index)

    def mark_failed(self, index: int, name: str, error: Exception) -> None:
        self._state[index] = StepState(name, 'failed', error=error)

    def get_completed_steps(self) -> list[int]:
        return list(self._order) # in completion order

    def was_completed(self, name: str) -> bool:
        return self._state.get(name, StepState('', 'pending')).status == 'completed'
