<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use App\Editorial\EditorialAggregate;
use Ec\Editorial\Domain\Model\Editorial;

final readonly class ResolverPipeline
{
    public function __construct(
        private EditorialMetadataResolver $metadataResolver,
        private ContentMediaResolver $contentMediaResolver,
        private RelatedContentResolver $relatedContentResolver,
        private MembershipResolver $membershipResolver,
    ) {
    }

    public function resolve(Editorial $editorial): EditorialAggregate
    {
        $metadata = $this->metadataResolver->resolve($editorial);

        $membershipPromise = $this->membershipResolver->startPromise(
            $editorial,
            $metadata->section->siteId(),
        );

        $contentMedia = $this->contentMediaResolver->resolve($editorial);

        $relatedContent = $this->relatedContentResolver->resolve($editorial);

        $multimedia = $this->contentMediaResolver->settleAllMultimedia(
            $contentMedia->multimediaResult,
            $relatedContent->combinedMultimediaResult(),
        );

        $multimediaOpening = $this->contentMediaResolver->mergeOpenings(
            $contentMedia->multimediaOpening,
            $relatedContent->insertedNews->multimediaResult,
            $relatedContent->recommended->multimediaResult,
        );

        $membershipLinks = $this->membershipResolver->resolve(
            $membershipPromise->promise,
            $membershipPromise->links,
        );

        return new EditorialAggregate(
            editorial: $editorial,
            section: $metadata->section,
            tags: $metadata->tags,
            signatures: $metadata->signatures,
            multimedia: $multimedia,
            multimediaOpening: $multimediaOpening,
            bodyPhotos: $contentMedia->bodyPhotos,
            insertedNews: $relatedContent->insertedNews->groups,
            recommendedEditorials: $relatedContent->recommended->groups,
            recommendedNews: $relatedContent->recommended->news,
            membershipLinks: $membershipLinks,
            commentCount: $metadata->commentCount,
        );
    }
}
