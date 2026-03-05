<?php declare(strict_types=1);

namespace PhpSaga\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \PhpSaga\TransactionResult run(array $steps, ?\PDO $db = null)
 *
 * @see \PhpSaga\Transaction
 */
class Saga extends Facade {
    protected static function getFacadeAccessor(): string {
        return 'phpsaga.transaction';
    }
}
