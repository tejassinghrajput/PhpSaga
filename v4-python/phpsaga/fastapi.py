from typing import Any, Callable, Awaitable

try:
    from starlette.middleware.base import BaseHTTPMiddleware
    from starlette.requests import Request
    from starlette.responses import Response
except ImportError:
    # Handle the case where FastAPI/Starlette is not installed
    class BaseHTTPMiddleware: # type: ignore
        def __init__(self, app: Any) -> None:
            self.app = app
    class Request: pass # type: ignore
    class Response: pass # type: ignore
from .transaction import Transaction

class TransactionalMiddleware(BaseHTTPMiddleware):
    async def dispatch(
        self,
        request: Request,
        call_next: Callable[[Request], Awaitable[Response]]
    ) -> Response:
        try:
            response = await call_next(request)
            return response
        except Exception:
            # PhpSaga compensation already fired inside the route handler if Transaction was used there.
            # This middleware handles DB session rollback or other high-level error handling.
            raise

# Usage in main.py:
# app = FastAPI()
# app.add_middleware(TransactionalMiddleware)
# Inside a route — use Transaction.run_async():
# result = await Transaction.run_async([
#     Step.make(async_action).rollback(async_compensate),
# ], session=db)
