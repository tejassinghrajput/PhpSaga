import pytest
from unittest.mock import MagicMock, patch
from phpsaga.django import transactional, TransactionalMixin
from phpsaga.fastapi import TransactionalMiddleware
from phpsaga import Step

def test_django_transactional_decorator():
    # Create a mock for django.db.transaction
    mock_django_db = MagicMock()

    with patch.dict('sys.modules', {'django': MagicMock(), 'django.db': mock_django_db, 'django.db.transaction': mock_django_db.transaction}):
        @transactional
        def my_view(request):
            return "view_result"

        result = my_view(MagicMock())
        assert result == "view_result"
        mock_django_db.transaction.atomic.assert_called_once()

def test_django_transactional_mixin():
    class BaseView:
        def dispatch(self, request, *args, **kwargs):
            return "dispatched"

    class MyView(TransactionalMixin, BaseView):
        def get_saga_steps(self):
            return [Step.make(lambda: "step1", "step1")]

    view = MyView()
    # Mock Transaction.run to return success
    with patch('phpsaga.transaction.Transaction.run') as mock_run:
        mock_run.return_value.success = True
        result = view.dispatch(MagicMock())
        assert result == "dispatched"
        mock_run.assert_called_once()

@pytest.mark.asyncio
async def test_fastapi_middleware():
    # Simple mock for call_next
    async def call_next(request):
        return "response"

    middleware = TransactionalMiddleware(MagicMock())
    result = await middleware.dispatch(MagicMock(), call_next)
    assert result == "response"

@pytest.mark.asyncio
async def test_fastapi_middleware_exception():
    async def call_next(request):
        raise ValueError("route failed")

    middleware = TransactionalMiddleware(MagicMock())
    with pytest.raises(ValueError, match="route failed"):
        await middleware.dispatch(MagicMock(), call_next)
