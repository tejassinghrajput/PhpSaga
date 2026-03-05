import asyncio
import pytest
from phpsaga import Step, Transaction, RollbackStrategy

def test_sync_happy_path():
    logs = []
    def step1(): logs.append("step1")
    def step2(): logs.append("step2")

    steps = [
        Step.make(step1, "step1"),
        Step.make(step2, "step2")
    ]

    result = Transaction.run(steps)

    assert result.success is True
    assert logs == ["step1", "step2"]
    assert len(result.audit_log) == 2
    assert result.audit_log[0][0] == 'STEP_COMPLETED'
    assert result.audit_log[0][1] == 'step1'

@pytest.mark.asyncio
async def test_async_happy_path():
    logs = []
    async def step1():
        await asyncio.sleep(0.01)
        logs.append("step1")
    async def step2():
        await asyncio.sleep(0.01)
        logs.append("step2")

    steps = [
        Step.make(step1, "step1"),
        Step.make(step2, "step2")
    ]

    result = await Transaction.run_async(steps)

    assert result.success is True
    assert logs == ["step1", "step2"]

@pytest.mark.asyncio
async def test_mixed_steps():
    logs = []
    def step1(): logs.append("step1")
    async def step2():
        await asyncio.sleep(0.01)
        logs.append("step2")
    def step3(): logs.append("step3")

    steps = [
        Step.make(step1, "step1"),
        Step.make(step2, "step2"),
        Step.make(step3, "step3")
    ]

    result = await Transaction.run_async(steps)

    assert result.success is True
    assert logs == ["step1", "step2", "step3"]

@pytest.mark.asyncio
async def test_compensation_order():
    logs = []
    def step1(): logs.append("step1")
    def comp1(): logs.append("comp1")
    def step2(): logs.append("step2")
    def comp2(): logs.append("comp2")
    def step3(): raise ValueError("fail")
    def comp3(): logs.append("comp3")

    steps = [
        Step.make(step1, "step1").rollback(comp1),
        Step.make(step2, "step2").rollback(comp2),
        Step.make(step3, "step3").rollback(comp3),
    ]

    result = await Transaction.run_async(steps)

    assert result.success is False
    assert result.failed_step == "step3"
    # Step 1 and 2 completed. Step 3 failed.
    # Compensation for 2, then 1. (Step 3 compensation shouldn't run as it failed)
    assert logs == ["step1", "step2", "comp2", "comp1"]

def test_sync_run_in_async_raises():
    async def run_sync():
        Transaction.run([])

    with pytest.raises(RuntimeError) as excinfo:
        asyncio.run(run_sync())
    assert "Use Transaction.run_async() in async context" in str(excinfo.value)
