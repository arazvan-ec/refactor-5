<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Body;

use App\Editorial\Assembler\Apps\Body\BodyTagInsertedNewsTransformer;
use App\Editorial\BodyTransformContext;
use App\Infrastructure\Service\MultimediaImageService;
use App\Infrastructure\Service\UrlGenerator;
use App\Tests\Editorial\Assembler\Apps\Body\DataProvider\BodyTagInsertedNewsDataProvider;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\EditorialTitles;
use Ec\Editorial\Exceptions\BodyDataTransformerNotFoundException;
use Ec\Multimedia\Domain\Model\Clipping;
use Ec\Multimedia\Domain\Model\Clippings;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(BodyTagInsertedNewsTransformer::class)]
final class BodyTagInsertedNewsTransformerTest extends TestCase
{
    private BodyTagInsertedNewsTransformer $transformer;
    /** @var UrlGenerator&MockObject */
    private UrlGenerator $urlGenerator;
    /** @var MultimediaImageService&MockObject */
    private MultimediaImageService $multimediaImageService;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGenerator::class);
        $this->multimediaImageService = $this->createMock(MultimediaImageService::class);
        $this->transformer = new BodyTagInsertedNewsTransformer(
            $this->urlGenerator,
            $this->multimediaImageService,
        );
    }

    #[Test]
    public function supportsShouldReturnBodyTagInsertedNewsClass(): void
    {
        static::assertSame(BodyTagInsertedNews::class, $this->transformer->supports());
    }

    /**
     * @param array<string, mixed>                             $data
     * @param array{signaturesWithIndexId: array<int, string>} $allSignatures
     * @param array<string, mixed>                             $expected
     */
    #[DataProviderExternal(BodyTagInsertedNewsDataProvider::class, 'getData')]
    #[Test]
    public function transformShouldReturnExpectedArrayWithMultimedia(array $data, array $allSignatures, array $expected): void
    {
        $id = 'editorial_id';
        $title = 'title body tag inserted news';
        $multimediaId = '1';

        $editorialMock = $this->createMock(Editorial::class);
        $sectionMock = $this->createMock(Section::class);

        $editorialIdBodyTagMock = $this->createMock(EditorialId::class);
        $editorialIdBodyTagMock->expects(static::once())
            ->method('id')
            ->willReturn($id);

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->expects(static::exactly(2))
            ->method('id')
            ->willReturn($id);

        $bodyElementMock = $this->createMock(BodyTagInsertedNews::class);
        $bodyElementMock->expects(static::once())
            ->method('editorialId')
            ->willReturn($editorialIdBodyTagMock);
        $bodyElementMock->expects(static::once())
            ->method('type')
            ->willReturn('bodytaginsertednews');

        $editorialMock->expects(static::exactly(2))
            ->method('id')
            ->willReturn($editorialIdMock);

        $editorialTitlesMock = $this->createMock(EditorialTitles::class);
        $editorialTitlesMock->expects(static::once())
            ->method('title')
            ->willReturn($title);

        $editorialMock->expects(static::exactly(2))
            ->method('editorialTitles')
            ->willReturn($editorialTitlesMock);

        $multimediaMock = $this->createMock(\Ec\Multimedia\Domain\Model\Multimedia::class);

        $shots = $expected['shots'];
        $this->multimediaImageService->expects(static::once())
            ->method('getShotsLandscape')
            ->with($multimediaMock)
            ->willReturn($shots);

        $this->urlGenerator->method('generateUrl')
            ->willReturn($expected['editorial']);

        $context = new BodyTransformContext(
            multimedia: [$multimediaId => $multimediaMock],
            multimediaOpening: [],
            insertedNews: [
                $id => [
                    'editorial' => $editorialMock,
                    'signatures' => $data['signaturesIndexes'],
                    'section' => $sectionMock,
                    'multimediaId' => $multimediaId,
                ],
            ],
            recommendedEditorials: [],
            bodyPhotos: [],
            membershipLinks: [],
        );

        $result = $this->transformer->transform($bodyElementMock, $context);

        static::assertSame($expected, $result);
    }

    /**
     * @param array<string, mixed>                             $data
     * @param array{signaturesWithIndexId: array<int, string>} $allSignatures
     * @param array<string, mixed>                             $expected
     */
    #[DataProviderExternal(BodyTagInsertedNewsDataProvider::class, 'getData')]
    #[Test]
    public function transformShouldReturnExpectedArrayWithMultimediaOpening(array $data, array $allSignatures, array $expected): void
    {
        $id = 'editorial_id';
        $title = 'title body tag inserted news';
        $multimediaId = '1';

        $editorialMock = $this->createMock(Editorial::class);
        $sectionMock = $this->createMock(Section::class);

        $editorialIdBodyTagMock = $this->createMock(EditorialId::class);
        $editorialIdBodyTagMock->expects(static::once())
            ->method('id')
            ->willReturn($id);

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->expects(static::exactly(2))
            ->method('id')
            ->willReturn($id);

        $bodyElementMock = $this->createMock(BodyTagInsertedNews::class);
        $bodyElementMock->expects(static::once())
            ->method('editorialId')
            ->willReturn($editorialIdBodyTagMock);
        $bodyElementMock->expects(static::once())
            ->method('type')
            ->willReturn('bodytaginsertednews');

        $editorialMock->expects(static::exactly(2))
            ->method('id')
            ->willReturn($editorialIdMock);

        $editorialTitlesMock = $this->createMock(EditorialTitles::class);
        $editorialTitlesMock->expects(static::once())
            ->method('title')
            ->willReturn($title);

        $editorialMock->expects(static::exactly(2))
            ->method('editorialTitles')
            ->willReturn($editorialTitlesMock);

        $multimediaPhotoMock = $this->createMock(MultimediaPhoto::class);
        $photoMock = $this->createMock(Photo::class);

        $shots = $expected['shots'];
        $openingData = ['opening' => $multimediaPhotoMock, 'resource' => $photoMock];

        $this->multimediaImageService->expects(static::once())
            ->method('getShotsLandscapeFromMedia')
            ->with($openingData)
            ->willReturn($shots);

        $this->urlGenerator->method('generateUrl')
            ->willReturn($expected['editorial']);

        $context = new BodyTransformContext(
            multimedia: [],
            multimediaOpening: [$multimediaId => $openingData],
            insertedNews: [
                $id => [
                    'editorial' => $editorialMock,
                    'signatures' => $data['signaturesIndexes'],
                    'section' => $sectionMock,
                    'multimediaId' => $multimediaId,
                ],
            ],
            recommendedEditorials: [],
            bodyPhotos: [],
            membershipLinks: [],
        );

        $result = $this->transformer->transform($bodyElementMock, $context);

        static::assertSame($expected, $result);
    }

    #[Test]
    public function transformShouldThrowExceptionWhenEditorialNotFound(): void
    {
        $editorialId = 'non_existent_editorial_id';

        $bodyElementMock = $this->createMock(BodyTagInsertedNews::class);
        $bodyElementMock->expects(static::once())
            ->method('type')
            ->willReturn('bodytaginsertednews');

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->expects(static::once())
            ->method('id')
            ->willReturn($editorialId);

        $bodyElementMock->expects(static::once())
            ->method('editorialId')
            ->willReturn($editorialIdMock);

        $context = new BodyTransformContext(
            multimedia: [],
            multimediaOpening: [],
            insertedNews: [
                'different_editorial_id' => [
                    'editorial' => $this->createMock(Editorial::class),
                    'signatures' => [],
                    'section' => $this->createMock(Section::class),
                    'multimediaId' => '1',
                ],
            ],
            recommendedEditorials: [],
            bodyPhotos: [],
            membershipLinks: [],
        );

        $this->expectException(BodyDataTransformerNotFoundException::class);
        $this->expectExceptionMessage('Inserted news: editorial not found for id: ' . $editorialId);

        $this->transformer->transform($bodyElementMock, $context);
    }
}
