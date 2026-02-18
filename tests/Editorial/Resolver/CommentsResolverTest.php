<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Ec\Snaapi\Infrastructure\Client\Http\QueryLegacyClient;
use App\Editorial\Resolver\CommentsResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommentsResolver::class)]
final class CommentsResolverTest extends TestCase
{
    #[Test]
    #[DataProvider('commentDataProvider')]
    public function resolveShouldReturnCommentCount(
        string $editorialId,
        array $apiResponse,
        int $expectedCount,
    ): void {
        $queryLegacyClient = $this->createMock(QueryLegacyClient::class);
        $queryLegacyClient
            ->expects(static::once())
            ->method('findCommentsByEditorialId')
            ->with($editorialId)
            ->willReturn($apiResponse);

        $resolver = new CommentsResolver($queryLegacyClient);

        $result = $resolver->resolve($editorialId);

        static::assertSame($expectedCount, $result);
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>, 2: int}>
     */
    public static function commentDataProvider(): array
    {
        return [
            'with comments' => [
                '12345',
                ['options' => ['totalrecords' => 42]],
                42,
            ],
            'without totalrecords key' => [
                '12345',
                ['options' => []],
                0,
            ],
            'empty options' => [
                '67890',
                ['options' => ['totalrecords' => 0]],
                0,
            ],
        ];
    }
}
