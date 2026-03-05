<?php declare(strict_types=1);

namespace PhpSaga\Symfony\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Transactional {
    public function __construct(
        // Which DB connection to use (matches Doctrine connection name)
        public readonly string $connection = 'default',
        // Force a brand new transaction even if one is already open
        public readonly bool $newTransaction = false,
    ) {}
}
