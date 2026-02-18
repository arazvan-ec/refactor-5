<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\Tag;

final readonly class EditorialMetadata
{
    /**
     * @param array<Tag>                       $tags
     * @param array<int, array<string, mixed>> $signatures
     */
    public function __construct(
        public Section $section,
        public array $tags,
        public array $signatures,
        public int $commentCount,
    ) {
    }
}
