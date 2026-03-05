<?php declare(strict_types=1);

namespace PhpSaga\Symfony\Tests;

use PHPUnit\Framework\TestCase;
use PhpSaga\Symfony\DependencyInjection\PhpSagaExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PhpSaga\Transaction;

class BundleTest extends TestCase {
    public function testExtensionRegistersTransactionService() {
        $container = new ContainerBuilder();
        $extension = new PhpSagaExtension();

        $extension->load([], $container);

        $this->assertTrue($container->has('phpsaga.transaction'));
        $this->assertInstanceOf(Transaction::class, $container->get('phpsaga.transaction'));
    }
}
