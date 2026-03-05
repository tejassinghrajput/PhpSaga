from functools import wraps
from typing import Any, Callable, TypeVar, cast
from .transaction import Transaction
from .step import Step

# Type variable for the view function
F = TypeVar('F', bound=Callable[..., Any])

# Decorator for function-based views
def transactional(view_func: F) -> F:
    @wraps(view_func)
    def wrapper(*args: Any, **kwargs: Any) -> Any:
        try:
            from django.db import transaction as django_transaction
            with django_transaction.atomic():
                return view_func(*args, **kwargs)
        except ImportError:
            # If django is not installed, just call the function
            return view_func(*args, **kwargs)
    return cast(F, wrapper)

# Mixin for class-based views
class TransactionalMixin:
    def get_saga_steps(self) -> list[Step]:
        # Override in subclass to return list of Step objects
        return []

    def dispatch(self, request: Any, *args: Any, **kwargs: Any) -> Any:
        result = Transaction.run(self.get_saga_steps())
        if not result.success:
            if result.error:
                raise result.error
            raise RuntimeError(f"Saga transaction failed at step: {result.failed_step}")
        return super().dispatch(request, *args, **kwargs) # type: ignore
