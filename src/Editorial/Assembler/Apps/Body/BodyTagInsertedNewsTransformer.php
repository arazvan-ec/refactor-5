<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Body;

use App\Editorial\BodyTransformContext;
use App\Infrastructure\Service\MultimediaImageService;
use App\Infrastructure\Service\UrlGenerator;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Exceptions\BodyDataTransformerNotFoundException;
use Ec\Encode\Encode;
use Ec\Multimedia\Domain\Model\Multimedia;
use Ec\Section\Domain\Model\Section;

final readonly class BodyTagInsertedNewsTransformer implements BodyElementTransformer
{
    public function __construct(
        private UrlGenerator $urlGenerator,
        private MultimediaImageService $multimediaImageService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(BodyElement $element, BodyTransformContext $context): array
    {
        \assert($element instanceof BodyTagInsertedNews);

        $editorialId = $element->editorialId()->id();

        if (!isset($context->insertedNews[$editorialId])) {
            throw new BodyDataTransformerNotFoundException(
                'Inserted news: editorial not found for id: ' . $editorialId
            );
        }

        /** @var array<string, mixed> $currentInsertedNews */
        $currentInsertedNews = $context->insertedNews[$editorialId];
        $signatures = $currentInsertedNews['signatures'];
        /** @var Editorial $editorial */
        $editorial = $currentInsertedNews['editorial'];
        /** @var Section $sectionInserted */
        $sectionInserted = $currentInsertedNews['section'];

        $elementArray = [
            'type' => $element->type(),
            'editorialId' => $editorial->id()->id(),
            'title' => $editorial->editorialTitles()->title(),
            'signatures' => $signatures,
            'editorial' => $this->editorialUrl($editorial, $sectionInserted),
        ];

        $shots = $this->getMultimediaOpening($editorialId, $context);

        if (empty($shots)) {
            $shots = $this->getMultimedia($editorialId, $context);
        }

        $elementArray['shots'] = $shots;
        $elementArray['photo'] = empty($shots) ? '' : reset($shots);

        return $elementArray;
    }

    /**
     * @return class-string<BodyElement>
     */
    public function supports(): string
    {
        return BodyTagInsertedNews::class;
    }

    private function editorialUrl(Editorial $editorial, Section $section): string
    {
        $editorialPath = \sprintf(
            '%s/%s/%s_%s',
            $section->getPath(),
            $editorial->publicationDate()->format('Y-m-d'),
            Encode::encodeUrl($editorial->editorialTitles()->urlTitle()),
            $editorial->id()->id(),
        );

        return $this->urlGenerator->generateUrl(
            'https://%s.%s.%s/%s',
            $section->isSubdomainBlog() ? 'blog' : 'www',
            $section->siteId(),
            $editorialPath,
        );
    }

    /**
     * @return array<string, string>
     */
    private function getMultimedia(string $editorialId, BodyTransformContext $context): array
    {
        /** @var string $multimediaId */
        $multimediaId = $context->insertedNews[$editorialId]['multimediaId'] ?? '';
        /** @var ?Multimedia $multimedia */
        $multimedia = $context->multimedia[$multimediaId] ?? null;

        if (null === $multimedia) {
            return [];
        }

        return $this->multimediaImageService->getShotsLandscape($multimedia);
    }

    /**
     * @return array<string, string>
     */
    private function getMultimediaOpening(string $editorialId, BodyTransformContext $context): array
    {
        /** @var string $multimediaId */
        $multimediaId = $context->insertedNews[$editorialId]['multimediaId'] ?? '';
        /** @var ?array{opening: \Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto, resource: \Ec\Multimedia\Domain\Model\Photo\Photo} $multimedia */
        $multimedia = $context->multimediaOpening[$multimediaId] ?? null;

        if (null === $multimedia) {
            return [];
        }

        return $this->multimediaImageService->getShotsLandscapeFromMedia($multimedia);
    }
}
