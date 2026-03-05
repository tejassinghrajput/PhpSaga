<?php declare(strict_types=1);

namespace PhpSaga\Laravel\Tests;

use Orchestra\Testbench\TestCase;
use PhpSaga\Laravel\PhpSagaServiceProvider;
use PhpSaga\Laravel\Facades\Saga;
use PhpSaga\Step;
use PhpSaga\TransactionResult;

class SagaTest extends TestCase {
    protected function getPackageProviders($app) {
        return [PhpSagaServiceProvider::class];
    }

    protected function getPackageAliases($app) {
        return [
            'Saga' => Saga::class,
        ];
    }

    public function testFacadeCanRunTransaction() {
        $step = Step::make(fn() => 'hello', 'hello')->rollback(fn() => null);

        $result = Saga::run([$step]);

        $this->assertInstanceOf(TransactionResult::class, $result);
        $this->assertTrue($result->wasSuccessful());
    }
}
