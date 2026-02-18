<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use Ec\Editorial\Domain\Model\Editorial;

final readonly class EditorialMetadataResolver
{
    public function __construct(
        private SectionResolver $sectionResolver,
        private TagsResolver $tagsResolver,
        private SignaturesResolver $signaturesResolver,
        private CommentsResolver $commentsResolver,
    ) {
    }

    public function resolve(Editorial $editorial): EditorialMetadata
    {
        $section = $this->sectionResolver->resolve($editorial);
        $tags = $this->tagsResolver->resolve($editorial);
        $signatures = $this->signaturesResolver->resolve($editorial, $section);
        $commentCount = $this->commentsResolver->resolve($editorial->id()->id());

        return new EditorialMetadata(
            section: $section,
            tags: $tags,
            signatures: $signatures,
            commentCount: $commentCount,
        );
    }
}
