<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use App\Infrastructure\Service\MultimediaImageService;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Editorial\Domain\Model\Signature;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Infrastructure\Client\Http\Media\QueryMultimediaClient as QueryMultimediaOpeningClient;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;
use Psr\Log\LoggerInterface;

final readonly class RecommendedResolver
{
    public function __construct(
        private QueryEditorialClient $queryEditorialClient,
        private QuerySectionClient $querySectionClient,
        private QueryMultimediaOpeningClient $queryMultimediaOpeningClient,
        private SignaturesResolver $signaturesResolver,
        private MultimediaResolver $multimediaResolver,
        private MultimediaImageService $multimediaImageService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Resolves recommended editorials.
     *
     * Returns typed RecommendedResult with groups, news, and unresolved multimedia data.
     */
    public function resolve(Editorial $editorial): RecommendedResult
    {
        $groups = [];
        $multimediaResult = new MultimediaResolutionResult();
        $news = [];

        $recommendedEditorials = $editorial->recommendedEditorials();

        /** @var EditorialId $recommendedEditorialId */
        foreach ($recommendedEditorials->editorialIds() as $recommendedEditorialId) {
            try {
                $idRecommended = $recommendedEditorialId->id();

                /** @var Editorial $recommendedEditorial */
                $recommendedEditorial = $this->queryEditorialClient->findEditorialById($idRecommended);

                if (!$recommendedEditorial->isVisible()) {
                    continue;
                }

                /** @var Section $sectionRecommended */
                $sectionRecommended = $this->querySectionClient->findSectionById($recommendedEditorial->sectionId());

                $signatures = $this->resolveSignaturesForEditorial($recommendedEditorial, $sectionRecommended);

                [$multimediaId, $editorialMultimediaResult] = $this->resolveMultimediaForEditorial($recommendedEditorial);
                $multimediaResult = $multimediaResult->merge($editorialMultimediaResult);

                $groups[$idRecommended] = [
                    'editorial' => $recommendedEditorial,
                    'section' => $sectionRecommended,
                    'signatures' => $signatures,
                    'multimediaId' => $multimediaId,
                ];

                $news[] = $recommendedEditorial;
            } catch (\Throwable $throwable) {
                $this->logger->error($throwable->getMessage());
                continue;
            }
        }

        return new RecommendedResult(
            groups: $groups,
            news: $news,
            multimediaResult: $multimediaResult,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveSignaturesForEditorial(Editorial $editorial, Section $section): array
    {
        $signatures = [];

        /** @var Signature $signature */
        foreach ($editorial->signatures()->getArrayCopy() as $signature) {
            $result = $this->signaturesResolver->retrieveAliasFormat(
                $signature->id()->id(),
                $section,
            );

            if (!empty($result)) {
                $signatures[] = $result;
            }
        }

        return $signatures;
    }

    /**
     * @return array{0: string, 1: MultimediaResolutionResult}
     */
    private function resolveMultimediaForEditorial(Editorial $editorial): array
    {
        $multimediaId = $this->multimediaImageService->getMultimediaId($editorial->multimedia());

        if (null !== $multimediaId && !empty($multimediaId->id())) {
            $promises = $this->multimediaResolver->startAsync([$multimediaId->id()]);

            return [$multimediaId->id(), new MultimediaResolutionResult(promises: $promises)];
        }

        $metaImageId = $editorial->metaImage();
        if (!empty($metaImageId)) {
            $multimediaOpening = $this->resolveMetaImage($metaImageId);

            return [$metaImageId, new MultimediaResolutionResult(multimediaOpening: $multimediaOpening)];
        }

        return ['', new MultimediaResolutionResult()];
    }

    /**
     * @return array<string, array{opening: MultimediaPhoto, resource: \Ec\Multimedia\Domain\Model\Photo\Photo}>
     */
    private function resolveMetaImage(string $metaImageId): array
    {
        try {
            $multimedia = $this->queryMultimediaOpeningClient->findMultimediaById($metaImageId);

            if (!$multimedia instanceof MultimediaPhoto) {
                return [];
            }

            $resource = $this->queryMultimediaOpeningClient->findPhotoById($multimedia->resourceId());

            return [
                $metaImageId => [
                    'opening' => $multimedia,
                    'resource' => $resource,
                ],
            ];
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());

            return [];
        }
    }
}
