<?php declare(strict_types=1);

namespace PhpSaga\Symfony\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use PhpSaga\Transaction;

class PhpSagaExtension extends Extension {
    public function load(array $configs, ContainerBuilder $container): void {
        $container->register('phpsaga.transaction', Transaction::class)
            ->setPublic(true);
    }
}
