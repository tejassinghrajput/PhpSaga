<?php declare(strict_types=1);

namespace PhpSaga\Symfony\EventListener;

use PhpSaga\Symfony\Attribute\Transactional;
use PhpSaga\Symfony\Service\SagaRunner;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use ReflectionMethod;

class TransactionalListener {
    public function __construct(
        private readonly SagaRunner $sagaRunner
    ) {}

    public function onKernelController(ControllerEvent $event): void {
        $controller = $event->getController();

        if (is_array($controller)) {
            $reflection = new ReflectionMethod($controller[0], $controller[1]);
        } elseif (is_object($controller) && method_exists($controller, '__invoke')) {
            $reflection = new ReflectionMethod($controller, '__invoke');
        } else {
            return;
        }

        $attributes = $reflection->getAttributes(Transactional::class);
        if (empty($attributes)) {
            return;
        }

        // Logic to wrap the controller call could be implemented here
        // For simplicity in this port, we just detect it.
    }
}
