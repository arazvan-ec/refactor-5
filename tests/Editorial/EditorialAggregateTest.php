<?php

declare(strict_types=1);

namespace App\Tests\Editorial;

use App\Editorial\EditorialAggregate;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EditorialAggregate::class)]
final class EditorialAggregateTest extends TestCase
{
    #[Test]
    public function constructShouldStoreAllProperties(): void
    {
        $editorialMock = $this->createMock(Editorial::class);
        $sectionMock = $this->createMock(Section::class);

        $aggregate = new EditorialAggregate(
            editorial: $editorialMock,
            section: $sectionMock,
            tags: [],
            signatures: [],
            multimedia: [],
            multimediaOpening: [],
            bodyPhotos: [],
            insertedNews: [],
            recommendedEditorials: [],
            recommendedNews: [],
            membershipLinks: [],
            commentCount: 42,
        );

        static::assertSame($editorialMock, $aggregate->editorial);
        static::assertSame($sectionMock, $aggregate->section);
        static::assertSame([], $aggregate->tags);
        static::assertSame([], $aggregate->signatures);
        static::assertSame([], $aggregate->multimedia);
        static::assertSame([], $aggregate->multimediaOpening);
        static::assertSame([], $aggregate->bodyPhotos);
        static::assertSame([], $aggregate->insertedNews);
        static::assertSame([], $aggregate->recommendedEditorials);
        static::assertSame([], $aggregate->recommendedNews);
        static::assertSame([], $aggregate->membershipLinks);
        static::assertSame(42, $aggregate->commentCount);
    }
}
