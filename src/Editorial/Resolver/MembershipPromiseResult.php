<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use Http\Promise\Promise;

final readonly class MembershipPromiseResult
{
    /**
     * @param array<int, string> $links
     */
    public function __construct(
        public ?Promise $promise,
        public array $links,
    ) {
    }
}
