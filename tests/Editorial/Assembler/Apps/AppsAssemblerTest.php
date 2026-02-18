<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps;

use App\Editorial\Assembler\Apps\AppsAssembler;
use App\Editorial\Assembler\Apps\Body\BodyTransformerPipeline;
use App\Editorial\Assembler\Apps\EditorialMetadataAssembler;
use App\Editorial\Assembler\Apps\MultimediaAssembler;
use App\Editorial\Assembler\Apps\RecommendedAssembler;
use App\Editorial\Assembler\Apps\SectionAssembler;
use App\Editorial\Assembler\Apps\SignaturesAssembler;
use App\Editorial\Assembler\Apps\StandfirstAssembler;
use App\Editorial\Assembler\Apps\TagsAssembler;
use App\Editorial\BodyTransformContext;
use App\Editorial\EditorialAggregate;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia as MultimediaEditorial;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Editorial\Domain\Model\Standfirst;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\Tag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AppsAssembler::class)]
final class AppsAssemblerTest extends TestCase
{
    private AppsAssembler $assembler;
    private MockObject&EditorialMetadataAssembler $editorialMetadataAssembler;
    private MockObject&SectionAssembler $sectionAssembler;
    private MockObject&TagsAssembler $tagsAssembler;
    private MockObject&SignaturesAssembler $signaturesAssembler;
    private MockObject&MultimediaAssembler $multimediaAssembler;
    private MockObject&StandfirstAssembler $standfirstAssembler;
    private MockObject&RecommendedAssembler $recommendedAssembler;
    private MockObject&BodyTransformerPipeline $bodyTransformerPipeline;

    protected function setUp(): void
    {
        $this->editorialMetadataAssembler = $this->createMock(EditorialMetadataAssembler::class);
        $this->sectionAssembler = $this->createMock(SectionAssembler::class);
        $this->tagsAssembler = $this->createMock(TagsAssembler::class);
        $this->signaturesAssembler = $this->createMock(SignaturesAssembler::class);
        $this->multimediaAssembler = $this->createMock(MultimediaAssembler::class);
        $this->standfirstAssembler = $this->createMock(StandfirstAssembler::class);
        $this->recommendedAssembler = $this->createMock(RecommendedAssembler::class);
        $this->bodyTransformerPipeline = $this->createMock(BodyTransformerPipeline::class);

        $this->assembler = new AppsAssembler(
            $this->editorialMetadataAssembler,
            $this->sectionAssembler,
            $this->tagsAssembler,
            $this->signaturesAssembler,
            $this->multimediaAssembler,
            $this->standfirstAssembler,
            $this->recommendedAssembler,
            $this->bodyTransformerPipeline,
        );
    }

    #[Test]
    public function assembleShouldComposeAllSubAssemblers(): void
    {
        $editorial = $this->createMock(Editorial::class);
        $section = $this->createMock(Section::class);
        $tag = $this->createMock(Tag::class);
        $body = $this->createMock(Body::class);
        $standfirst = $this->createMock(Standfirst::class);
        $opening = $this->createMock(Opening::class);
        $multimedia = $this->createMock(MultimediaEditorial::class);

        $editorial->method('body')->willReturn($body);
        $editorial->method('standFirst')->willReturn($standfirst);
        $editorial->method('opening')->willReturn($opening);
        $editorial->method('multimedia')->willReturn($multimedia);

        $aggregate = new EditorialAggregate(
            editorial: $editorial,
            section: $section,
            tags: [$tag],
            signatures: [['name' => 'Author']],
            multimedia: [],
            multimediaOpening: [],
            bodyPhotos: [],
            insertedNews: [],
            recommendedEditorials: [],
            recommendedNews: [],
            membershipLinks: [],
            commentCount: 42,
        );

        $this->editorialMetadataAssembler->expects(static::once())
            ->method('assemble')
            ->with($editorial, $section)
            ->willReturn(['id' => '123', 'url' => 'https://example.com']);

        $this->sectionAssembler->expects(static::once())
            ->method('assembleSection')
            ->with($section)
            ->willReturn(['id' => 's-1', 'name' => 'Section', 'url' => 'https://section.com', 'encodeName' => 'section']);

        $this->sectionAssembler->expects(static::exactly(2))
            ->method('assembleOptions')
            ->with($section)
            ->willReturn([['id' => 's-1', 'name' => 'Section', 'url' => 'https://section.com', 'encodeName' => 'section']]);

        $this->tagsAssembler->expects(static::once())
            ->method('assemble')
            ->with([$tag], $section)
            ->willReturn([['id' => 't-1', 'name' => 'Tag', 'url' => 'https://tag.com']]);

        $this->bodyTransformerPipeline->expects(static::once())
            ->method('transformBody')
            ->with($body, static::isInstanceOf(BodyTransformContext::class))
            ->willReturn(['type' => 'body', 'elements' => []]);

        $this->standfirstAssembler->expects(static::once())
            ->method('assemble')
            ->with($standfirst, static::isInstanceOf(BodyTransformContext::class))
            ->willReturn([]);

        $this->recommendedAssembler->expects(static::once())
            ->method('assemble')
            ->willReturn([]);

        $result = $this->assembler->assemble($aggregate);

        static::assertSame('123', $result['id']);
        static::assertSame('https://example.com', $result['url']);
        static::assertSame('s-1', $result['section']['id']);
        static::assertCount(1, $result['tags']);
        static::assertSame(42, $result['countComments']);
        static::assertSame([['name' => 'Author']], $result['signatures']);
        static::assertSame(['type' => 'body', 'elements' => []], $result['body']);
        static::assertSame([], $result['standfirst']);
        static::assertSame([], $result['recommendedEditorials']);
    }

    #[Test]
    public function assembleShouldUseOpeningMultimediaWhenAvailable(): void
    {
        $editorial = $this->createMock(Editorial::class);
        $section = $this->createMock(Section::class);
        $body = $this->createMock(Body::class);
        $standfirst = $this->createMock(Standfirst::class);
        $opening = $this->createMock(Opening::class);
        $multimedia = $this->createMock(MultimediaEditorial::class);

        $editorial->method('body')->willReturn($body);
        $editorial->method('standFirst')->willReturn($standfirst);
        $editorial->method('opening')->willReturn($opening);
        $editorial->method('multimedia')->willReturn($multimedia);

        $multimediaOpeningData = ['mm-1' => ['opening' => 'data', 'resource' => 'photo']];

        $aggregate = new EditorialAggregate(
            editorial: $editorial,
            section: $section,
            tags: [],
            signatures: [],
            multimedia: [],
            multimediaOpening: $multimediaOpeningData,
            bodyPhotos: [],
            insertedNews: [],
            recommendedEditorials: [],
            recommendedNews: [],
            membershipLinks: [],
            commentCount: 0,
        );

        $this->editorialMetadataAssembler->method('assemble')->willReturn([]);
        $this->sectionAssembler->method('assembleSection')->willReturn(['id' => '', 'name' => '', 'url' => '', 'encodeName' => '']);
        $this->sectionAssembler->method('assembleOptions')->willReturn([]);
        $this->tagsAssembler->method('assemble')->willReturn([]);
        $this->bodyTransformerPipeline->method('transformBody')->willReturn(['type' => '', 'elements' => []]);
        $this->standfirstAssembler->method('assemble')->willReturn([]);
        $this->recommendedAssembler->method('assemble')->willReturn([]);

        $expectedMultimedia = ['id' => 'mm-1', 'type' => 'photo'];

        $this->multimediaAssembler->expects(static::once())
            ->method('assembleOpening')
            ->with($multimediaOpeningData, $opening)
            ->willReturn($expectedMultimedia);

        $result = $this->assembler->assemble($aggregate);

        static::assertSame($expectedMultimedia, $result['multimedia']);
    }

    #[Test]
    public function assembleShouldFallbackToEditorialMultimediaWhenNoOpening(): void
    {
        $editorial = $this->createMock(Editorial::class);
        $section = $this->createMock(Section::class);
        $body = $this->createMock(Body::class);
        $standfirst = $this->createMock(Standfirst::class);
        $opening = $this->createMock(Opening::class);
        $multimediaEditorial = $this->createMock(MultimediaEditorial::class);

        $editorial->method('body')->willReturn($body);
        $editorial->method('standFirst')->willReturn($standfirst);
        $editorial->method('opening')->willReturn($opening);
        $editorial->method('multimedia')->willReturn($multimediaEditorial);

        $multimediaData = ['mm-1' => $this->createMock(\Ec\Multimedia\Domain\Model\Multimedia\Multimedia::class)];

        $aggregate = new EditorialAggregate(
            editorial: $editorial,
            section: $section,
            tags: [],
            signatures: [],
            multimedia: $multimediaData,
            multimediaOpening: [],
            bodyPhotos: [],
            insertedNews: [],
            recommendedEditorials: [],
            recommendedNews: [],
            membershipLinks: [],
            commentCount: 0,
        );

        $this->editorialMetadataAssembler->method('assemble')->willReturn([]);
        $this->sectionAssembler->method('assembleSection')->willReturn(['id' => '', 'name' => '', 'url' => '', 'encodeName' => '']);
        $this->sectionAssembler->method('assembleOptions')->willReturn([]);
        $this->tagsAssembler->method('assemble')->willReturn([]);
        $this->bodyTransformerPipeline->method('transformBody')->willReturn(['type' => '', 'elements' => []]);
        $this->standfirstAssembler->method('assemble')->willReturn([]);
        $this->recommendedAssembler->method('assemble')->willReturn([]);

        $expectedMultimedia = ['id' => 'mm-1', 'type' => 'photo', 'caption' => 'Test'];

        $this->multimediaAssembler->expects(static::once())
            ->method('assembleEditorial')
            ->with($multimediaData, $multimediaEditorial)
            ->willReturn($expectedMultimedia);

        $result = $this->assembler->assemble($aggregate);

        static::assertSame($expectedMultimedia, $result['multimedia']);
    }

    #[Test]
    public function assembleShouldReturnNullMultimediaWhenNoneAvailable(): void
    {
        $editorial = $this->createMock(Editorial::class);
        $section = $this->createMock(Section::class);
        $body = $this->createMock(Body::class);
        $standfirst = $this->createMock(Standfirst::class);
        $opening = $this->createMock(Opening::class);
        $multimedia = $this->createMock(MultimediaEditorial::class);

        $editorial->method('body')->willReturn($body);
        $editorial->method('standFirst')->willReturn($standfirst);
        $editorial->method('opening')->willReturn($opening);
        $editorial->method('multimedia')->willReturn($multimedia);

        $aggregate = new EditorialAggregate(
            editorial: $editorial,
            section: $section,
            tags: [],
            signatures: [],
            multimedia: [],
            multimediaOpening: [],
            bodyPhotos: [],
            insertedNews: [],
            recommendedEditorials: [],
            recommendedNews: [],
            membershipLinks: [],
            commentCount: 0,
        );

        $this->editorialMetadataAssembler->method('assemble')->willReturn([]);
        $this->sectionAssembler->method('assembleSection')->willReturn(['id' => '', 'name' => '', 'url' => '', 'encodeName' => '']);
        $this->sectionAssembler->method('assembleOptions')->willReturn([]);
        $this->tagsAssembler->method('assemble')->willReturn([]);
        $this->bodyTransformerPipeline->method('transformBody')->willReturn(['type' => '', 'elements' => []]);
        $this->standfirstAssembler->method('assemble')->willReturn([]);
        $this->recommendedAssembler->method('assemble')->willReturn([]);

        $result = $this->assembler->assemble($aggregate);

        static::assertNull($result['multimedia']);
    }

    #[Test]
    public function assembleShouldPassAdsOptionsAndAnaliticsOptionsBothAsAssembleOptions(): void
    {
        $editorial = $this->createMock(Editorial::class);
        $section = $this->createMock(Section::class);
        $body = $this->createMock(Body::class);
        $standfirst = $this->createMock(Standfirst::class);
        $opening = $this->createMock(Opening::class);
        $multimedia = $this->createMock(MultimediaEditorial::class);

        $editorial->method('body')->willReturn($body);
        $editorial->method('standFirst')->willReturn($standfirst);
        $editorial->method('opening')->willReturn($opening);
        $editorial->method('multimedia')->willReturn($multimedia);

        $aggregate = new EditorialAggregate(
            editorial: $editorial,
            section: $section,
            tags: [],
            signatures: [],
            multimedia: [],
            multimediaOpening: [],
            bodyPhotos: [],
            insertedNews: [],
            recommendedEditorials: [],
            recommendedNews: [],
            membershipLinks: [],
            commentCount: 0,
        );

        $options = [['id' => 's-1', 'name' => 'Section', 'url' => 'https://example.com', 'encodeName' => 'section']];

        $this->editorialMetadataAssembler->method('assemble')->willReturn([]);
        $this->sectionAssembler->method('assembleSection')->willReturn(['id' => '', 'name' => '', 'url' => '', 'encodeName' => '']);
        $this->sectionAssembler->method('assembleOptions')->willReturn($options);
        $this->tagsAssembler->method('assemble')->willReturn([]);
        $this->bodyTransformerPipeline->method('transformBody')->willReturn(['type' => '', 'elements' => []]);
        $this->standfirstAssembler->method('assemble')->willReturn([]);
        $this->recommendedAssembler->method('assemble')->willReturn([]);

        $result = $this->assembler->assemble($aggregate);

        static::assertSame($options, $result['adsOptions']);
        static::assertSame($options, $result['analiticsOptions']);
    }
}
