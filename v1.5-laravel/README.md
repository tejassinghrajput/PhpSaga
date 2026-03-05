# PhpSaga Laravel Package

First-class Laravel experience for the PhpSaga transaction library.

## Installation

```bash
composer require phpsaga/laravel
```

The package will automatically register itself using Laravel's package discovery.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=phpsaga-config
```

## Usage

### Using the Facade

```php
use Saga;
use PhpSaga\Step;

$result = Saga::run([
    Step::make(fn() => Account::debit($from, $amount), 'debit')
        ->rollback(fn() => Account::credit($from, $amount)),

    Step::make(fn() => Account::credit($to, $amount), 'credit')
        ->rollback(fn() => Account::debit($to, $amount)),
]);
```

### Transactional Middleware

You can wrap routes in a database transaction automatically:

```php
Route::post('/transfer', [TransferController::class, 'handle'])
    ->middleware('transactional');
```

Note: You need to register the middleware in your `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ...
    'transactional' => \PhpSaga\Laravel\Middleware\Transactional::class,
];
```

### Generator Command

Create a new Saga class:

```bash
php artisan make:saga TransferFundsSaga
```

This will create a class in `app/Sagas/TransferFundsSaga.php`.

## SagaStep Interface

For more complex steps, you can implement the `SagaStep` interface:

```php
namespace App\Sagas\Steps;

use PhpSaga\Laravel\Contracts\SagaStep;
use PhpSaga\Step;

class ChargeCreditCard implements SagaStep {
    public function execute(): mixed {
        return Payment::charge($this->amount);
    }

    public function compensate(): void {
        Payment::refund($this->transactionId);
    }

    public function getStepName(): string {
        return 'charge_credit_card';
    }

    public function getCompensationFailureStrategy(): int {
        return Step::RETRY;
    }
}
```
