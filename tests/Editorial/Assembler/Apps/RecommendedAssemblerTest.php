<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps;

use App\Editorial\Assembler\Apps\RecommendedAssembler;
use App\Infrastructure\Service\MultimediaImageService;
use App\Infrastructure\Service\UrlGenerator;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\EditorialTitles;
use Ec\Multimedia\Domain\Model\Multimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(RecommendedAssembler::class)]
final class RecommendedAssemblerTest extends TestCase
{
    private RecommendedAssembler $assembler;
    private MockObject&MultimediaImageService $multimediaImageService;

    protected function setUp(): void
    {
        $this->multimediaImageService = $this->createMock(MultimediaImageService::class);
        $this->assembler = new RecommendedAssembler(
            new UrlGenerator('dev'),
            $this->multimediaImageService
        );
    }

    #[Test]
    public function assembleShouldReturnEmptyArrayWhenNoRecommendedNews(): void
    {
        $result = $this->assembler->assemble([], [], [], []);

        static::assertSame([], $result);
    }

    #[Test]
    public function assembleShouldReturnCorrectStructureWithMultimediaShots(): void
    {
        $editorialId = '7422';
        $multimediaId = '2532';
        $expectedShots = [
            '202w' => 'https://example.com/202.jpg',
            '144w' => 'https://example.com/144.jpg',
            '128w' => 'https://example.com/128.jpg',
        ];

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $titlesMock = $this->createMock(EditorialTitles::class);
        $titlesMock->method('title')->willReturn('Recommended Title');
        $titlesMock->method('urlTitle')->willReturn('recommended-title');

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('id')->willReturn($editorialIdMock);
        $editorialMock->method('editorialTitles')->willReturn($titlesMock);
        $editorialMock->method('publicationDate')->willReturn(new \DateTime('2023-01-01'));

        $sectionMock = $this->createMock(Section::class);
        $sectionMock->method('getPath')->willReturn('espana');
        $sectionMock->method('isSubdomainBlog')->willReturn(false);
        $sectionMock->method('siteId')->willReturn('1');

        $multimediaMock = $this->createMock(Multimedia::class);

        $this->multimediaImageService->method('getShotsLandscape')
            ->with($multimediaMock)
            ->willReturn($expectedShots);

        $recommendedEditorials = [
            $editorialId => [
                'editorial' => $editorialMock,
                'section' => $sectionMock,
                'signatures' => [['name' => 'Author']],
                'multimediaId' => $multimediaId,
            ],
        ];

        $multimedia = [
            $multimediaId => $multimediaMock,
        ];

        $result = $this->assembler->assemble(
            [$editorialMock],
            $recommendedEditorials,
            $multimedia,
            []
        );

        static::assertCount(1, $result);
        static::assertSame('recommendededitorial', $result[0]['type']);
        static::assertSame($editorialId, $result[0]['editorialId']);
        static::assertSame('Recommended Title', $result[0]['title']);
        static::assertSame($expectedShots, $result[0]['shots']);
        static::assertSame('https://example.com/202.jpg', $result[0]['photo']);
        static::assertSame([['name' => 'Author']], $result[0]['signatures']);
    }

    #[Test]
    public function assembleShouldPreferMultimediaOpeningOverMultimedia(): void
    {
        $editorialId = '7422';
        $multimediaId = '2532';
        $openingShots = [
            '202w' => 'https://opening.example.com/202.jpg',
            '144w' => 'https://opening.example.com/144.jpg',
            '128w' => 'https://opening.example.com/128.jpg',
        ];

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $titlesMock = $this->createMock(EditorialTitles::class);
        $titlesMock->method('title')->willReturn('Title');
        $titlesMock->method('urlTitle')->willReturn('title');

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('id')->willReturn($editorialIdMock);
        $editorialMock->method('editorialTitles')->willReturn($titlesMock);
        $editorialMock->method('publicationDate')->willReturn(new \DateTime());

        $sectionMock = $this->createMock(Section::class);
        $sectionMock->method('getPath')->willReturn('path');
        $sectionMock->method('isSubdomainBlog')->willReturn(false);
        $sectionMock->method('siteId')->willReturn('1');

        $multimediaOpeningData = $this->createMock(MultimediaPhoto::class);
        $resourceData = $this->createMock(Photo::class);

        $recommendedEditorials = [
            $editorialId => [
                'editorial' => $editorialMock,
                'section' => $sectionMock,
                'signatures' => [],
                'multimediaId' => $multimediaId,
            ],
        ];

        $multimediaOpening = [
            $multimediaId => [
                'opening' => $multimediaOpeningData,
                'resource' => $resourceData,
            ],
        ];

        $this->multimediaImageService->method('getShotsLandscapeFromMedia')
            ->willReturn($openingShots);

        $result = $this->assembler->assemble(
            [$editorialMock],
            $recommendedEditorials,
            [],
            $multimediaOpening
        );

        static::assertSame($openingShots, $result[0]['shots']);
        static::assertSame('https://opening.example.com/202.jpg', $result[0]['photo']);
    }

    #[Test]
    public function assembleShouldReturnEmptyPhotoWhenNoMultimediaAvailable(): void
    {
        $editorialId = '7422';
        $multimediaId = '2532';

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $titlesMock = $this->createMock(EditorialTitles::class);
        $titlesMock->method('title')->willReturn('Title');
        $titlesMock->method('urlTitle')->willReturn('title');

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('id')->willReturn($editorialIdMock);
        $editorialMock->method('editorialTitles')->willReturn($titlesMock);
        $editorialMock->method('publicationDate')->willReturn(new \DateTime());

        $sectionMock = $this->createMock(Section::class);
        $sectionMock->method('getPath')->willReturn('path');
        $sectionMock->method('isSubdomainBlog')->willReturn(false);
        $sectionMock->method('siteId')->willReturn('1');

        $recommendedEditorials = [
            $editorialId => [
                'editorial' => $editorialMock,
                'section' => $sectionMock,
                'signatures' => [],
                'multimediaId' => $multimediaId,
            ],
        ];

        $result = $this->assembler->assemble(
            [$editorialMock],
            $recommendedEditorials,
            [],
            []
        );

        static::assertSame([], $result[0]['shots']);
        static::assertSame('', $result[0]['photo']);
    }

    #[Test]
    public function assembleShouldHandleMultipleRecommendedEditorials(): void
    {
        $editorials = [];
        $recommendedEditorials = [];

        for ($i = 1; $i <= 3; ++$i) {
            $editorialIdMock = $this->createMock(EditorialId::class);
            $editorialIdMock->method('id')->willReturn((string) $i);

            $titlesMock = $this->createMock(EditorialTitles::class);
            $titlesMock->method('title')->willReturn('Title '.$i);
            $titlesMock->method('urlTitle')->willReturn('title-'.$i);

            $editorialMock = $this->createMock(Editorial::class);
            $editorialMock->method('id')->willReturn($editorialIdMock);
            $editorialMock->method('editorialTitles')->willReturn($titlesMock);
            $editorialMock->method('publicationDate')->willReturn(new \DateTime());

            $sectionMock = $this->createMock(Section::class);
            $sectionMock->method('getPath')->willReturn('path');
            $sectionMock->method('isSubdomainBlog')->willReturn(false);
            $sectionMock->method('siteId')->willReturn('1');

            $editorials[] = $editorialMock;
            $recommendedEditorials[(string) $i] = [
                'editorial' => $editorialMock,
                'section' => $sectionMock,
                'signatures' => [],
                'multimediaId' => 'mm-'.$i,
            ];
        }

        $result = $this->assembler->assemble(
            $editorials,
            $recommendedEditorials,
            [],
            []
        );

        static::assertCount(3, $result);
        static::assertSame('Title 1', $result[0]['title']);
        static::assertSame('Title 2', $result[1]['title']);
        static::assertSame('Title 3', $result[2]['title']);
    }
}
