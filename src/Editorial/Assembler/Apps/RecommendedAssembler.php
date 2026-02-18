<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps;

use App\Infrastructure\Service\MultimediaImageService;
use App\Infrastructure\Service\UrlGenerator;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Encode\Encode;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Section\Domain\Model\Section;

final readonly class RecommendedAssembler
{
    private const TYPE = 'recommendededitorial';

    public function __construct(
        private UrlGenerator $urlGenerator,
        private MultimediaImageService $multimediaImageService,
    ) {
    }

    /**
     * @param array<Editorial>                                                                                      $recommendedNews
     * @param array<string, array{editorial: Editorial, section: Section, signatures: array, multimediaId: string}> $recommendedEditorials
     * @param array<string, AbstractMultimedia>                                                                     $multimedia
     * @param array<string, array{opening: MultimediaPhoto, resource: Photo}>                                       $multimediaOpening
     *
     * @return array<int, array<string, mixed>>
     */
    public function assemble(
        array $recommendedNews,
        array $recommendedEditorials,
        array $multimedia,
        array $multimediaOpening,
    ): array {
        $recommended = [];

        foreach ($recommendedNews as $editorial) {
            $editorialId = $editorial->id()->id();

            /** @var array{editorial: Editorial, section: Section, signatures: array, multimediaId: string} $currentRecommended */
            $currentRecommended = $recommendedEditorials[$editorialId];
            $signatures = $currentRecommended['signatures'];

            /** @var Section $section */
            $section = $currentRecommended['section'];

            $elementArray = [];
            $elementArray['type'] = self::TYPE;
            $elementArray['editorialId'] = $editorialId;
            $elementArray['signatures'] = $signatures;
            $elementArray['editorial'] = $this->buildEditorialUrl($editorial, $section);
            $elementArray['title'] = $editorial->editorialTitles()->title();

            $shots = $this->getMultimediaOpening($editorialId, $recommendedEditorials, $multimediaOpening);

            if (empty($shots)) {
                $shots = $this->getMultimedia($editorialId, $recommendedEditorials, $multimedia);
            }

            $elementArray['shots'] = $shots;
            $elementArray['photo'] = empty($shots) ? '' : reset($shots);

            $recommended[] = $elementArray;
        }

        return $recommended;
    }

    private function buildEditorialUrl(Editorial $editorial, Section $section): string
    {
        $editorialPath = \sprintf(
            '%s/%s/%s_%s',
            $section->getPath(),
            $editorial->publicationDate()->format('Y-m-d'),
            Encode::encodeUrl($editorial->editorialTitles()->urlTitle()),
            $editorial->id()->id()
        );

        return $this->urlGenerator->generateUrl(
            'https://%s.%s.%s/%s',
            $section->isSubdomainBlog() ? 'blog' : 'www',
            $section->siteId(),
            $editorialPath
        );
    }

    /**
     * @param array<string, array{editorial: Editorial, section: Section, signatures: array, multimediaId: string}> $recommendedEditorials
     * @param array<string, AbstractMultimedia>                                                                     $multimedia
     *
     * @return array<string, string>
     */
    private function getMultimedia(
        string $editorialId,
        array $recommendedEditorials,
        array $multimedia,
    ): array {
        /** @var array{multimediaId: string} $currentRecommended */
        $currentRecommended = $recommendedEditorials[$editorialId];

        /** @var \Ec\Multimedia\Domain\Model\Multimedia|null $multimediaModel */
        $multimediaModel = $multimedia[$currentRecommended['multimediaId']] ?? null;

        if (null === $multimediaModel) {
            return [];
        }

        return $this->multimediaImageService->getShotsLandscape($multimediaModel);
    }

    /**
     * @param array<string, array{editorial: Editorial, section: Section, signatures: array, multimediaId: string}> $recommendedEditorials
     * @param array<string, array{opening: MultimediaPhoto, resource: Photo}>                                       $multimediaOpening
     *
     * @return array<string, string>
     */
    private function getMultimediaOpening(
        string $editorialId,
        array $recommendedEditorials,
        array $multimediaOpening,
    ): array {
        /** @var array{multimediaId: string} $currentRecommended */
        $currentRecommended = $recommendedEditorials[$editorialId];

        /** @var array{opening: MultimediaPhoto, resource: Photo}|null $multimedia */
        $multimedia = $multimediaOpening[$currentRecommended['multimediaId']] ?? null;

        if (null === $multimedia) {
            return [];
        }

        return $this->multimediaImageService->getShotsLandscapeFromMedia($multimedia);
    }
}
