<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use App\Infrastructure\Service\MultimediaImageService;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Editorial\Domain\Model\Signature;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Infrastructure\Client\Http\Media\QueryMultimediaClient as QueryMultimediaOpeningClient;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;
use Http\Promise\Promise;
use Psr\Log\LoggerInterface;

final readonly class InsertedNewsResolver
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
     * Resolves inserted news editorials from body elements.
     *
     * Returns EditorialGroup data and unresolved multimedia promises for batch settling.
     *
     * @return array{
     *     groups: array<string, array{editorial: Editorial, section: Section, signatures: array<int, array<string, mixed>>, multimediaId: string}>,
     *     promises: array<int, Promise>,
     *     multimediaOpening: array<string, array{opening: MultimediaPhoto, resource: \Ec\Multimedia\Domain\Model\Photo\Photo}>
     * }
     */
    public function resolve(Editorial $editorial): array
    {
        $groups = [];
        $promises = [];
        $multimediaOpening = [];

        /** @var BodyTagInsertedNews[] $insertedNewsElements */
        $insertedNewsElements = $editorial->body()->bodyElementsOf(BodyTagInsertedNews::class);

        foreach ($insertedNewsElements as $insertedNewsElement) {
            $idInserted = $insertedNewsElement->editorialId()->id();

            try {
                /** @var Editorial $insertedEditorial */
                $insertedEditorial = $this->queryEditorialClient->findEditorialById($idInserted);

                if (!$insertedEditorial->isVisible()) {
                    continue;
                }

                /** @var Section $sectionInserted */
                $sectionInserted = $this->querySectionClient->findSectionById($insertedEditorial->sectionId());

                $signatures = $this->resolveSignaturesForEditorial($insertedEditorial, $sectionInserted);

                [$multimediaId, $newPromises, $newOpening] = $this->resolveMultimediaForEditorial($insertedEditorial);
                $promises = array_merge($promises, $newPromises);
                $multimediaOpening = array_merge($multimediaOpening, $newOpening);

                $groups[$idInserted] = [
                    'editorial' => $insertedEditorial,
                    'section' => $sectionInserted,
                    'signatures' => $signatures,
                    'multimediaId' => $multimediaId,
                ];
            } catch (\Throwable $throwable) {
                $this->logger->error($throwable->getMessage());
                continue;
            }
        }

        return [
            'groups' => $groups,
            'promises' => $promises,
            'multimediaOpening' => $multimediaOpening,
        ];
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
     * Resolves multimedia for an inserted/recommended editorial.
     *
     * If the editorial has a multimedia ID, starts an async promise.
     * If not, falls back to metaImage (opening-style resolution).
     *
     * @return array{0: string, 1: array<int, Promise>, 2: array<string, array{opening: MultimediaPhoto, resource: \Ec\Multimedia\Domain\Model\Photo\Photo}>}
     */
    private function resolveMultimediaForEditorial(Editorial $editorial): array
    {
        $promises = [];
        $multimediaOpening = [];

        $multimediaId = $this->multimediaImageService->getMultimediaId($editorial->multimedia());

        if (null !== $multimediaId && !empty($multimediaId->id())) {
            $promises = $this->multimediaResolver->startAsync([$multimediaId->id()]);

            return [$multimediaId->id(), $promises, $multimediaOpening];
        }

        $metaImageId = $editorial->metaImage();
        if (!empty($metaImageId)) {
            $multimediaOpening = $this->resolveMetaImage($metaImageId);
        }

        return [$metaImageId, $promises, $multimediaOpening];
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
