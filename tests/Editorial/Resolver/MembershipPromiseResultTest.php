<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\Resolver\MembershipPromiseResult;
use Http\Promise\Promise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MembershipPromiseResult::class)]
final class MembershipPromiseResultTest extends TestCase
{
    #[Test]
    public function shouldStorePromiseAndLinks(): void
    {
        $promise = $this->createMock(Promise::class);
        $links = ['link1', 'link2'];

        $result = new MembershipPromiseResult(promise: $promise, links: $links);

        static::assertSame($promise, $result->promise);
        static::assertSame($links, $result->links);
    }

    #[Test]
    public function shouldAllowNullPromise(): void
    {
        $result = new MembershipPromiseResult(promise: null, links: []);

        static::assertNull($result->promise);
        static::assertSame([], $result->links);
    }
}
