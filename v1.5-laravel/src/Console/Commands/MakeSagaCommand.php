<?php declare(strict_types=1);

namespace PhpSaga\Laravel\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeSagaCommand extends GeneratorCommand {
    protected $name = 'make:saga';
    protected $description = 'Create a new Saga class';
    protected $type = 'Saga';

    protected function getStub(): string {
        return __DIR__ . '/stubs/saga.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string {
        return $rootNamespace . '\Sagas';
    }
}
