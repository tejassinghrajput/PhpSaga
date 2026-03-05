import { Step, Transaction } from '../src';

describe('Transaction', () => {
    it('should run a happy path', async () => {
        const logs: string[] = [];
        const step1 = Step.make(() => logs.push('step1'), 'step1').rollback(() => {});
        const step2 = Step.make(async () => logs.push('step2'), 'step2').rollback(() => {});

        const result = await Transaction.run([step1, step2]);

        expect(result.success).toBe(true);
        expect(logs).toEqual(['step1', 'step2']);
    });

    it('should compensate in reverse order', async () => {
        const logs: string[] = [];
        const step1 = Step.make(() => {}, 'step1').rollback(() => logs.push('comp1'));
        const step2 = Step.make(() => {}, 'step2').rollback(() => logs.push('comp2'));
        const step3 = Step.make(() => { throw new Error('fail'); }, 'step3').rollback(() => {});

        const result = await Transaction.run([step1, step2, step3]);

        expect(result.success).toBe(false);
        expect(result.failedStep).toBe('step3');
        expect(logs).toEqual(['comp2', 'comp1']);
    });
});
