<?php

declare(strict_types=1);

namespace App\Editorial;

use Ec\Editorial\Domain\Model\Editorial;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Section\Domain\Model\Section;

/**
 * Typed context that body element transformers receive.
 * Replaces the untyped array $resolveData bag.
 * Constructed once from EditorialAggregate and passed through the pipeline.
 */
final readonly class BodyTransformContext
{
    /**
     * @param array<string, AbstractMultimedia>   $multimedia
     * @param array<string, array{opening: MultimediaPhoto, resource: Photo}> $multimediaOpening
     * @param array<string, array{editorial: Editorial, section: Section, signatures: array, multimediaId: string}> $insertedNews
     * @param array<string, array{editorial: Editorial, section: Section, signatures: array, multimediaId: string}> $recommendedEditorials
     * @param array<string, Photo>               $bodyPhotos
     * @param array<string, mixed>               $membershipLinks
     */
    public function __construct(
        public array $multimedia,
        public array $multimediaOpening,
        public array $insertedNews,
        public array $recommendedEditorials,
        public array $bodyPhotos,
        public array $membershipLinks,
    ) {
    }

    /** Factory: builds from the aggregate after resolve phase */
    public static function fromAggregate(EditorialAggregate $aggregate): self
    {
        return new self(
            multimedia: $aggregate->multimedia,
            multimediaOpening: $aggregate->multimediaOpening,
            insertedNews: $aggregate->insertedNews,
            recommendedEditorials: $aggregate->recommendedEditorials,
            bodyPhotos: $aggregate->bodyPhotos,
            membershipLinks: $aggregate->membershipLinks,
        );
    }
}
