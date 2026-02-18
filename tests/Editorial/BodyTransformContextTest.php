<?php

declare(strict_types=1);

namespace App\Tests\Editorial;

use App\Editorial\BodyTransformContext;
use App\Editorial\EditorialAggregate;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(BodyTransformContext::class)]
class BodyTransformContextTest extends TestCase
{
    #[Test]
    public function constructorShouldSetAllProperties(): void
    {
        $multimedia = $this->createMock(AbstractMultimedia::class);
        $openingMultimedia = $this->createMock(MultimediaPhoto::class);
        $openingResource = $this->createMock(Photo::class);
        $photo = $this->createMock(Photo::class);
        $insertedEditorial = $this->createMock(Editorial::class);
        $insertedSection = $this->createMock(Section::class);

        $multimediaArray = ['mm-1' => $multimedia];
        $multimediaOpening = ['open-1' => ['opening' => $openingMultimedia, 'resource' => $openingResource]];
        $insertedNews = ['ins-1' => [
            'editorial' => $insertedEditorial,
            'section' => $insertedSection,
            'signatures' => [],
            'multimediaId' => 'mm-ins-1',
        ]];
        $recommendedEditorials = [];
        $bodyPhotos = ['photo-1' => $photo];
        $membershipLinks = ['link1' => 'https://membership.example.com'];

        $context = new BodyTransformContext(
            multimedia: $multimediaArray,
            multimediaOpening: $multimediaOpening,
            insertedNews: $insertedNews,
            recommendedEditorials: $recommendedEditorials,
            bodyPhotos: $bodyPhotos,
            membershipLinks: $membershipLinks,
        );

        $this->assertSame($multimediaArray, $context->multimedia);
        $this->assertSame($multimediaOpening, $context->multimediaOpening);
        $this->assertSame($insertedNews, $context->insertedNews);
        $this->assertSame($recommendedEditorials, $context->recommendedEditorials);
        $this->assertSame($bodyPhotos, $context->bodyPhotos);
        $this->assertSame($membershipLinks, $context->membershipLinks);
    }

    #[Test]
    public function constructorShouldAcceptEmptyArrays(): void
    {
        $context = new BodyTransformContext(
            multimedia: [],
            multimediaOpening: [],
            insertedNews: [],
            recommendedEditorials: [],
            bodyPhotos: [],
            membershipLinks: [],
        );

        $this->assertSame([], $context->multimedia);
        $this->assertSame([], $context->multimediaOpening);
        $this->assertSame([], $context->insertedNews);
        $this->assertSame([], $context->recommendedEditorials);
        $this->assertSame([], $context->bodyPhotos);
        $this->assertSame([], $context->membershipLinks);
    }

    #[Test]
    public function fromAggregateShouldBuildContextFromAggregate(): void
    {
        $editorial = $this->createMock(Editorial::class);
        $section = $this->createMock(Section::class);
        $multimedia = $this->createMock(AbstractMultimedia::class);
        $photo = $this->createMock(Photo::class);
        $openingMultimedia = $this->createMock(MultimediaPhoto::class);
        $openingResource = $this->createMock(Photo::class);

        $multimediaArray = ['mm-1' => $multimedia];
        $multimediaOpening = ['open-1' => ['opening' => $openingMultimedia, 'resource' => $openingResource]];
        $bodyPhotos = ['photo-1' => $photo];
        $insertedNews = ['ins-1' => [
            'editorial' => $editorial,
            'section' => $section,
            'signatures' => [],
            'multimediaId' => 'mm-1',
        ]];
        $recommendedEditorials = [];
        $membershipLinks = ['link1' => 'https://example.com'];

        $aggregate = new EditorialAggregate(
            editorial: $editorial,
            section: $section,
            tags: [],
            signatures: [],
            multimedia: $multimediaArray,
            multimediaOpening: $multimediaOpening,
            bodyPhotos: $bodyPhotos,
            insertedNews: $insertedNews,
            recommendedEditorials: $recommendedEditorials,
            recommendedNews: [],
            membershipLinks: $membershipLinks,
            commentCount: 5,
        );

        $context = BodyTransformContext::fromAggregate($aggregate);

        $this->assertSame($multimediaArray, $context->multimedia);
        $this->assertSame($multimediaOpening, $context->multimediaOpening);
        $this->assertSame($insertedNews, $context->insertedNews);
        $this->assertSame($recommendedEditorials, $context->recommendedEditorials);
        $this->assertSame($bodyPhotos, $context->bodyPhotos);
        $this->assertSame($membershipLinks, $context->membershipLinks);
    }

    #[Test]
    public function fromAggregateShouldNotIncludeNonContextFields(): void
    {
        $editorial = $this->createMock(Editorial::class);
        $section = $this->createMock(Section::class);

        $aggregate = new EditorialAggregate(
            editorial: $editorial,
            section: $section,
            tags: [],
            signatures: ['alias1' => ['name' => 'John']],
            multimedia: [],
            multimediaOpening: [],
            bodyPhotos: [],
            insertedNews: [],
            recommendedEditorials: [],
            recommendedNews: [],
            membershipLinks: [],
            commentCount: 10,
        );

        $context = BodyTransformContext::fromAggregate($aggregate);

        $this->assertSame([], $context->multimedia);
        $this->assertSame([], $context->multimediaOpening);
        $this->assertSame([], $context->bodyPhotos);
        $this->assertSame([], $context->insertedNews);
        $this->assertSame([], $context->recommendedEditorials);
        $this->assertSame([], $context->membershipLinks);
    }
}
