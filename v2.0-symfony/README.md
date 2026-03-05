# PhpSaga Symfony Bundle

Symfony integration for the PhpSaga transaction library.

## Installation

```bash
composer require phpsaga/symfony-bundle
```

Add the bundle to your `config/bundles.php`:

```php
return [
    // ...
    PhpSaga\Symfony\PhpSagaBundle::class => ['all' => true],
];
```

## Usage

### Injecting the SagaRunner

The `SagaRunner` service is automatically registered and can be autowired in your services or controllers.

```php
namespace App\Controller;

use PhpSaga\Symfony\Service\SagaRunner;
use PhpSaga\Step;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController {
    public function checkout(SagaRunner $saga, OrderService $orders) {
        $result = $saga->run([
            Step::make(fn() => $orders->create(), 'create_order')
                ->rollback(fn() => $orders->cancel()),
            // ...
        ]);

        // ...
    }
}
```

### Using the Transactional Attribute

You can annotate controller methods with `#[Transactional]` to signal that they should be handled as sagas.

```php
use PhpSaga\Symfony\Attribute\Transactional;

#[Transactional]
public function submitOrder() {
    // ...
}
```

### SagaStepInterface

Implement `SagaStepInterface` for service-based steps:

```php
namespace App\Saga;

use PhpSaga\Symfony\Contracts\SagaStepInterface;
use PhpSaga\Step;

class InventoryStep implements SagaStepInterface {
    public function execute(): mixed {
        // Reserve inventory
    }

    public function compensate(): void {
        // Release inventory
    }

    public function getStepName(): string {
        return 'reserve_inventory';
    }

    public function getCompensationFailureStrategy(): int {
        return Step::RETRY;
    }
}
```
