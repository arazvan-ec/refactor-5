<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\Resolver\MultimediaResolutionResult;
use Http\Promise\Promise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MultimediaResolutionResult::class)]
final class MultimediaResolutionResultTest extends TestCase
{
    #[Test]
    public function constructorShouldDefaultToEmptyArrays(): void
    {
        $result = new MultimediaResolutionResult();

        static::assertSame([], $result->promises);
        static::assertSame([], $result->multimediaOpening);
    }

    #[Test]
    public function mergeShouldCombinePromisesAndOpenings(): void
    {
        $promise1 = $this->createMock(Promise::class);
        $promise2 = $this->createMock(Promise::class);

        $result1 = new MultimediaResolutionResult(
            promises: [$promise1],
            multimediaOpening: ['key1' => ['data' => 'opening1']],
        );

        $result2 = new MultimediaResolutionResult(
            promises: [$promise2],
            multimediaOpening: ['key2' => ['data' => 'opening2']],
        );

        $merged = $result1->merge($result2);

        static::assertCount(2, $merged->promises);
        static::assertSame($promise1, $merged->promises[0]);
        static::assertSame($promise2, $merged->promises[1]);
        static::assertCount(2, $merged->multimediaOpening);
        static::assertArrayHasKey('key1', $merged->multimediaOpening);
        static::assertArrayHasKey('key2', $merged->multimediaOpening);
    }

    #[Test]
    public function mergeShouldReturnNewInstanceWithoutMutatingOriginal(): void
    {
        $promise = $this->createMock(Promise::class);

        $original = new MultimediaResolutionResult(promises: [$promise]);
        $other = new MultimediaResolutionResult(
            multimediaOpening: ['key' => ['data' => 'value']],
        );

        $merged = $original->merge($other);

        static::assertNotSame($original, $merged);
        static::assertCount(1, $original->promises);
        static::assertSame([], $original->multimediaOpening);
        static::assertCount(1, $merged->promises);
        static::assertCount(1, $merged->multimediaOpening);
    }

    #[Test]
    public function mergeWithEmptyResultShouldReturnEquivalentResult(): void
    {
        $promise = $this->createMock(Promise::class);

        $result = new MultimediaResolutionResult(
            promises: [$promise],
            multimediaOpening: ['key' => ['data' => 'value']],
        );

        $merged = $result->merge(new MultimediaResolutionResult());

        static::assertCount(1, $merged->promises);
        static::assertCount(1, $merged->multimediaOpening);
    }
}
