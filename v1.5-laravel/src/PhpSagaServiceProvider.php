<?php declare(strict_types=1);

namespace PhpSaga\Laravel;

use Illuminate\Support\ServiceProvider;
use PhpSaga\Transaction;
use PhpSaga\Step;

class PhpSagaServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->mergeConfigFrom(__DIR__ . '/../config/phpsaga.php', 'phpsaga');

        $this->app->bind('phpsaga.transaction', function ($app) {
            return new Transaction();
        });
    }

    public function boot(): void {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/phpsaga.php' => config_path('phpsaga.php'),
            ], 'config');
        }
    }
}
