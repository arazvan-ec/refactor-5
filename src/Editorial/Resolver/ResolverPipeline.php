<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use App\Editorial\EditorialAggregate;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\Multimedia\Widget;

/**
 * Orchestrates all resolvers to build an EditorialAggregate.
 *
 * The pipeline follows this execution order:
 * 1. Section (synchronous, needed by other resolvers)
 * 2. All other resolvers collect data and promises
 * 3. Single batch settle for all multimedia promises
 * 4. Membership promise resolution
 * 5. Build the immutable EditorialAggregate
 */
final readonly class ResolverPipeline
{
    public function __construct(
        private SectionResolver $sectionResolver,
        private TagsResolver $tagsResolver,
        private SignaturesResolver $signaturesResolver,
        private MultimediaResolver $multimediaResolver,
        private OpeningResolver $openingResolver,
        private BodyPhotosResolver $bodyPhotosResolver,
        private InsertedNewsResolver $insertedNewsResolver,
        private RecommendedResolver $recommendedResolver,
        private MembershipResolver $membershipResolver,
        private CommentsResolver $commentsResolver,
    ) {
    }

    public function resolve(Editorial $editorial): EditorialAggregate
    {
        // Step 1: Resolve section first (needed by signatures)
        $section = $this->sectionResolver->resolve($editorial);

        // Step 2: Start membership promise early
        $membershipResult = $this->membershipResolver->startPromise($editorial, $section->siteId());

        // Step 3: Resolve tags
        $tags = $this->tagsResolver->resolve($editorial);

        // Step 4: Resolve signatures for the main editorial
        $signatures = $this->signaturesResolver->resolve($editorial, $section);

        // Step 5: Start async multimedia for the main editorial
        $mainMultimediaId = $this->multimediaResolver->extractMultimediaId($editorial);
        $allPromises = [];

        if (null !== $mainMultimediaId && !$editorial->multimedia() instanceof Widget) {
            $allPromises = $this->multimediaResolver->startAsync([$mainMultimediaId]);
        }

        // Step 6: Resolve opening (synchronous)
        $multimediaOpening = $this->openingResolver->resolve($editorial);

        // Step 7: Resolve body photos
        $bodyPhotos = $this->bodyPhotosResolver->resolve($editorial->body());

        // Step 8: Resolve inserted news (returns groups + unresolved promises)
        $insertedResult = $this->insertedNewsResolver->resolve($editorial);

        // Step 9: Resolve recommended editorials (returns groups + unresolved promises + news)
        $recommendedResult = $this->recommendedResolver->resolve($editorial);

        // Step 10: Resolve comments
        $commentCount = $this->commentsResolver->resolve($editorial->id()->id());

        // Step 11: Merge ALL multimedia promises and settle in a single batch
        $allPromises = array_merge(
            $allPromises,
            $insertedResult['promises'],
            $recommendedResult['promises'],
        );

        $multimedia = $this->multimediaResolver->settle($allPromises);

        // Step 12: Merge multimedia opening data from inserted/recommended
        $multimediaOpening = array_merge(
            $multimediaOpening,
            $insertedResult['multimediaOpening'],
            $recommendedResult['multimediaOpening'],
        );

        // Step 13: Resolve membership links
        $membershipLinks = $this->membershipResolver->resolve(
            $membershipResult['promise'],
            $membershipResult['links'],
        );

        // Step 14: Build the aggregate
        return new EditorialAggregate(
            editorial: $editorial,
            section: $section,
            tags: $tags,
            signatures: $signatures,
            multimedia: $multimedia,
            multimediaOpening: $multimediaOpening,
            bodyPhotos: $bodyPhotos,
            insertedNews: $insertedResult['groups'],
            recommendedEditorials: $recommendedResult['groups'],
            recommendedNews: $recommendedResult['news'],
            membershipLinks: $membershipLinks,
            commentCount: $commentCount,
        );
    }
}
