export enum RollbackStrategy {
    RETRY = 'RETRY',
    LOG_AND_CONTINUE = 'LOG_AND_CONTINUE',
    ALERT_HUMAN = 'ALERT_HUMAN',
    THROW = 'THROW'
}

export type Action = () => Promise<any> | any;

export class Step {
    private compensation: Action | null = null;
    private rollbackStrategy: RollbackStrategy = RollbackStrategy.LOG_AND_CONTINUE;

    private constructor(private readonly name: string, private readonly action: Action) {}

    static make(action: Action, name: string = ''): Step {
        return new Step(name || action.name || 'unnamed_step', action);
    }

    rollback(compensation: Action): this {
        this.compensation = compensation;
        return this;
    }

    onRollbackFailure(strategy: RollbackStrategy): this {
        this.rollbackStrategy = strategy;
        return this;
    }

    getName(): string { return this.name; }
    getAction(): Action { return this.action; }
    getCompensation(): Action | null { return this.compensation; }
    getRollbackStrategy(): RollbackStrategy { return this.rollbackStrategy; }

    validate(): void {
        if (this.compensation === null) {
            throw new Error(`Step '${this.name}' produces side effects. You MUST define rollback()`);
        }
    }
}

export interface LogEntry {
    event: string;
    context: any;
    timestamp: number;
}

export class TransactionResult {
    constructor(
        public readonly success: boolean,
        public readonly auditLog: LogEntry[],
        public readonly failedStep: string | null = null,
        public readonly error: Error | null = null
    ) {}
}

export class Transaction {
    static async run(steps: Step[]): Promise<TransactionResult> {
        const auditLog: LogEntry[] = [];
        const completedSteps: Step[] = [];

        const log = (event: string, context: any = {}) => {
            auditLog.push({ event, context, timestamp: Date.now() });
        };

        try {
            for (const step of steps) {
                step.validate();
                log('STEP_STARTED', { step: step.getName() });

                try {
                    await step.getAction()();
                    completedSteps.push(step);
                    log('STEP_COMPLETED', { step: step.getName() });
                } catch (e: any) {
                    log('STEP_FAILED', { step: step.getName(), error: e.message });
                    throw { step: step.getName(), error: e };
                }
            }

            log('TRANSACTION_COMPLETED');
            return new TransactionResult(true, auditLog);
        } catch (failInfo: any) {
            const failedStepName = failInfo.step;

            for (const step of completedSteps.reverse()) {
                const compensation = step.getCompensation();
                if (compensation) {
                    log('COMPENSATION_STARTED', { step: step.getName() });
                    try {
                        await compensation();
                        log('COMPENSATION_COMPLETED', { step: step.getName() });
                    } catch (e: any) {
                        log('COMPENSATION_FAILED', { step: step.getName(), error: e.message });
                        if (step.getRollbackStrategy() === RollbackStrategy.THROW) {
                            throw e;
                        }
                    }
                }
            }

            log('TRANSACTION_ROLLED_BACK');
            return new TransactionResult(false, auditLog, failedStepName, failInfo.error);
        }
    }
}
