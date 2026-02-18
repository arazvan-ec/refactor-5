<?php

declare(strict_types=1);

namespace App\Editorial;

use Ec\Editorial\Domain\Model\Editorial;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\Tag;

/**
 * Immutable DTO that carries all resolved data between the Resolve and Assemble phases.
 */
final readonly class EditorialAggregate
{
    /**
     * @param array<Tag>                                          $tags
     * @param array<int, array<string, mixed>>                    $signatures
     * @param array<string, AbstractMultimedia>                   $multimedia
     * @param array<string, array{opening: MultimediaPhoto, resource: Photo}> $multimediaOpening
     * @param array<string, Photo>                                $bodyPhotos
     * @param array<string, array{editorial: Editorial, section: Section, signatures: array<int, array<string, mixed>>, multimediaId: string}> $insertedNews
     * @param array<string, array{editorial: Editorial, section: Section, signatures: array<int, array<string, mixed>>, multimediaId: string}> $recommendedEditorials
     * @param array<Editorial>                                    $recommendedNews
     * @param array<string, mixed>                                $membershipLinks
     */
    public function __construct(
        public Editorial $editorial,
        public Section $section,
        public array $tags,
        public array $signatures,
        public array $multimedia,
        public array $multimediaOpening,
        public array $bodyPhotos,
        public array $insertedNews,
        public array $recommendedEditorials,
        public array $recommendedNews,
        public array $membershipLinks,
        public int $commentCount,
    ) {
    }
}
